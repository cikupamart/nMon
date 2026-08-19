package collector

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"runtime"
	"time"
)

type DockerCollector struct {
	socketPath string
	client     *http.Client
}

type DockerInfo struct {
	ServerVersion string        `json:"ServerVersion"`
	Containers    int           `json:"Containers"`
	ContainersRunning int       `json:"ContainersRunning"`
	ContainersStopped  int       `json:"ContainersStopped"`
	Images        int           `json:"Images"`
	NCPU         int            `json:"NCPU"`
	MemTotal     int64          `json:"MemTotal"`
	OSType       string         `json:"OSType"`
}

type DockerContainer struct {
	ID         string            `json:"Id"`
	Name       string            `json:"Names"`
	Image      string            `json:"Image"`
	State      string            `json:"State"`
	Status     string            `json:"Status"`
	Ports      string            `json:"Ports"`
	SizeRw     int64             `json:"SizeRw"`
	SizeRootFs int64             `json:"SizeRootFs"`
	Labels     map[string]string `json:"Labels"`
	Created    int64             `json:"Created"`
}

type DockerContainerStats struct {
	CPUStats struct {
		CPUUsage struct {
			TotalUsage int64 `json:"total_usage"`
			PreCPUUsage int64 `json:"precpu_usage"`
		} `json:"cpu_usage"`
		SystemCPUUsage int64 `json:"system_cpu_usage"`
		OnlineCPUs      int   `json:"online_cpus"`
	} `json:"cpu_stats"`
	PreCPUStats struct {
		CPUUsage struct {
			TotalUsage int64 `json:"total_usage"`
		} `json:"cpu_usage"`
		SystemCPUUsage int64 `json:"system_cpu_usage"`
	} `json:"precpu_stats"`
	MemoryStats struct {
		Usage   int64            `json:"usage"`
		Limit   int64            `json:"limit"`
		Stats   map[string]int64 `json:"stats"`
	} `json:"memory_stats"`
	Networks map[string]struct {
		RxBytes   int64 `json:"rx_bytes"`
		TxBytes   int64 `json:"tx_bytes"`
		RxPackets int64 `json:"rx_packets"`
		TxPackets int64 `json:"tx_packets"`
	} `json:"networks"`
	BlkioStats struct {
		IOServiceBytes []struct {
			Value int64 `json:"value"`
		} `json:"io_service_bytes_recursive"`
	} `json:"blkio_stats"`
}

type DockerNetwork struct {
	Name      string `json:"Name"`
	ID        string `json:"Id"`
	Driver    string `json:"Driver"`
	IPAM      struct {
		Config []struct {
			Subnet  string `json:"Subnet"`
			Gateway string `json:"Gateway"`
		} `json:"Config"`
	} `json:"IPAM"`
	Containers map[string]struct {
		Name string `json:"Name"`
	} `json:"Containers"`
}

func NewDockerCollector() *DockerCollector {
	socketPath := "/var/run/docker.sock"
	if runtime.GOOS == "windows" {
		socketPath = "//./pipe/docker_engine"
	}

	return &DockerCollector{
		socketPath: socketPath,
		client: &http.Client{
			Transport: &http.Transport{
				DialContext: func(_ context.Context, _, _ string) (interface{}, error) {
					return dialSocket(socketPath)
				},
			},
			Timeout: 10 * time.Second,
		},
	}
}

func (c *DockerCollector) IsAvailable() bool {
	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, "GET", "http://localhost/version", nil)
	if err != nil {
		return false
	}

	resp, err := c.client.Do(req)
	if err != nil {
		return false
	}
	defer resp.Body.Close()

	return resp.StatusCode == http.StatusOK
}

func (c *DockerCollector) GetDockerInfo() (*DockerInfo, error) {
	resp, err := c.client.Get("http://localhost/info")
	if err != nil {
		return nil, fmt.Errorf("failed to get Docker info: %w", err)
	}
	defer resp.Body.Close()

	var info DockerInfo
	if err := json.NewDecoder(resp.Body).Decode(&info); err != nil {
		return nil, fmt.Errorf("failed to decode Docker info: %w", err)
	}

	return &info, nil
}

func (c *DockerCollector) ListContainers(all bool) ([]DockerContainer, error) {
	url := "http://localhost/containers/json"
	if all {
		url += "?all=true"
	}

	resp, err := c.client.Get(url)
	if err != nil {
		return nil, fmt.Errorf("failed to list containers: %w", err)
	}
	defer resp.Body.Close()

	var containers []DockerContainer
	if err := json.NewDecoder(resp.Body).Decode(&containers); err != nil {
		return nil, fmt.Errorf("failed to decode containers: %w", err)
	}

	return containers, nil
}

func (c *DockerCollector) GetContainerStats(containerID string) (*DockerContainerStats, error) {
	url := fmt.Sprintf("http://localhost/containers/%s/stats?stream=false", containerID)
	
	resp, err := c.client.Get(url)
	if err != nil {
		return nil, fmt.Errorf("failed to get container stats: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read stats: %w", err)
	}

	var stats DockerContainerStats
	if err := json.Unmarshal(body, &stats); err != nil {
		return nil, fmt.Errorf("failed to decode stats: %w", err)
	}

	return &stats, nil
}

func (c *DockerCollector) ListNetworks() ([]DockerNetwork, error) {
	resp, err := c.client.Get("http://localhost/networks")
	if err != nil {
		return nil, fmt.Errorf("failed to list networks: %w", err)
	}
	defer resp.Body.Close()

	var networks []DockerNetwork
	if err := json.NewDecoder(resp.Body).Decode(&networks); err != nil {
		return nil, fmt.Errorf("failed to decode networks: %w", err)
	}

	return networks, nil
}

func (c *DockerCollector) CollectDockerMetrics() (map[string]interface{}, error) {
	metrics := make(map[string]interface{})

	// Get Docker info
	info, err := c.GetDockerInfo()
	if err != nil {
		return nil, err
	}

	metrics["info"] = map[string]interface{}{
		"version":             info.ServerVersion,
		"total_containers":    info.Containers,
		"running_containers":  info.ContainersRunning,
		"stopped_containers":  info.ContainersStopped,
		"images":              info.Images,
		"cpus":                info.NCPU,
		"memory_total":        info.MemTotal,
	}

	// Get all containers
	containers, err := c.ListContainers(true)
	if err != nil {
		return nil, err
	}

	var containerList []map[string]interface{}
	var totalCPU float64
	var totalMemory int64
	var totalNetworkRx int64
	var totalNetworkTx int64

	for _, container := range containers {
		containerInfo := map[string]interface{}{
			"id":      container.ID[:12],
			"name":    container.Name,
			"image":   container.Image,
			"state":   container.State,
			"status":  container.Status,
			"created": time.Unix(container.Created, 0).Format(time.RFC3339),
		}

		// Get stats for running containers
		if container.State == "running" {
			stats, err := c.GetContainerStats(container.ID)
			if err == nil {
				// Calculate CPU usage
				cpuDelta := float64(stats.CPUStats.CPUUsage.TotalUsage - stats.PreCPUStats.CPUUsage.TotalUsage)
				systemDelta := float64(stats.CPUStats.SystemCPUUsage - stats.PreCPUStats.SystemCPUUsage)
				cpuUsage := 0.0
				if systemDelta > 0 && cpuDelta > 0 {
					cpuUsage = (cpuDelta / systemDelta) * float64(stats.CPUStats.OnlineCPUs) * 100
				}
				totalCPU += cpuUsage

				// Calculate memory usage
				memUsage := stats.MemoryStats.Usage
				totalMemory += memUsage
				memLimit := stats.MemoryStats.Limit
				memPercent := 0.0
				if memLimit > 0 {
					memPercent = (float64(memUsage) / float64(memLimit)) * 100
				}

				// Network stats
				var netRx, netTx int64
				for _, net := range stats.Networks {
					netRx += net.RxBytes
					netTx += net.TxBytes
				}
				totalNetworkRx += netRx
				totalNetworkTx += netTx

				containerInfo["stats"] = map[string]interface{}{
					"cpu_percent":    cpuUsage,
					"memory_usage":   memUsage,
					"memory_limit":   memLimit,
					"memory_percent": memPercent,
					"network_rx":     netRx,
					"network_tx":     netTx,
				}
			}
		}

		containerList = append(containerList, containerInfo)
	}

	metrics["containers"] = containerList
	metrics["summary"] = map[string]interface{}{
		"total_containers": len(containers),
		"total_cpu":        totalCPU,
		"total_memory":     totalMemory,
		"total_network_rx": totalNetworkRx,
		"total_network_tx": totalNetworkTx,
	}

	// Get networks
	networks, err := c.ListNetworks()
	if err == nil {
		var networkList []map[string]interface{}
		for _, net := range networks {
			networkList = append(networkList, map[string]interface{}{
				"name":     net.Name,
				"id":       net.ID[:12],
				"driver":   net.Driver,
				"subnet":   net.IPAM.Config[0].Subnet,
				"gateway":  net.IPAM.Config[0].Gateway,
				"containers": len(net.Containers),
			})
		}
		metrics["networks"] = networkList
	}

	return metrics, nil
}
