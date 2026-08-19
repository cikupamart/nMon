package collector

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"strings"
	"time"
)

type KubernetesCollector struct {
	client    *http.Client
	inCluster bool
	token     string
	caCert    []byte
	host      string
}

type NodeInfo struct {
	Metadata struct {
		Name string `json:"name"`
	} `json:"metadata"`
	Status struct {
		Conditions []struct {
			Type   string `json:"type"`
			Status string `json:"status"`
		} `json:"conditions"`
		Addresses []struct {
			Type    string `json:"type"`
			Address string `json:"address"`
		} `json:"addresses"`
		Capacity struct {
			CPU    string `json:"cpu"`
			Memory string `json:"memory"`
		} `json:"capacity"`
		Allocatable struct {
			CPU    string `json:"cpu"`
			Memory string `json:"memory"`
		} `json:"allocatable"`
		NodeInfo struct {
			OperatingSystem string `json:"operatingSystem"`
			Architecture    string `json:"architecture"`
			KubeletVersion   string `json:"kubeletVersion"`
		} `json:"nodeInfo"`
	} `json:"status"`
}

type PodInfo struct {
	Metadata struct {
		Name      string            `json:"name"`
		Namespace string            `json:"namespace"`
		Labels    map[string]string `json:"labels"`
	} `json:"metadata"`
	Status struct {
		Phase             string `json:"phase"`
		HostIP            string `json:"hostIP"`
		PodIP             string `json:"podIP"`
		StartTime         string `json:"startTime"`
		ContainerStatuses []struct {
			Name         string `json:"name"`
			Ready        bool   `json:"ready"`
			RestartCount int    `json:"restartCount"`
			Image        string `json:"image"`
		} `json:"containerStatuses"`
		Conditions []struct {
			Type               string `json:"type"`
			Status             string `json:"status"`
			LastTransitionTime string `json:"lastTransitionTime"`
		} `json:"conditions"`
	} `json:"status"`
	Spec struct {
		NodeName   string `json:"nodeName"`
		Containers []struct {
			Name  string `json:"name"`
			Image string `json:"image"`
			Resources struct {
				Requests struct {
					CPU    string `json:"cpu"`
					Memory string `json:"memory"`
				} `json:"requests"`
				Limits struct {
					CPU    string `json:"cpu"`
					Memory string `json:"memory"`
				} `json:"limits"`
			} `json:"resources"`
		} `json:"containers"`
	} `json:"spec"`
}

type DeploymentInfo struct {
	Metadata struct {
		Name      string `json:"name"`
		Namespace string `json:"namespace"`
	} `json:"metadata"`
	Spec struct {
		Replicas int `json:"replicas"`
	} `json:"spec"`
	Status struct {
		Replicas            int `json:"replicas"`
		ReadyReplicas       int `json:"readyReplicas"`
		AvailableReplicas   int `json:"availableReplicas"`
		UnavailableReplicas int `json:"unavailableReplicas"`
	} `json:"status"`
}

type NamespaceInfo struct {
	Metadata struct {
		Name   string            `json:"name"`
		Labels map[string]string `json:"labels"`
	} `json:"metadata"`
	Status struct {
		Phase string `json:"phase"`
	} `json:"status"`
}

func NewKubernetesCollector() *KubernetesCollector {
	token, _ := os.ReadFile("/var/run/secrets/kubernetes.io/serviceaccount/token")
	caCert, _ := os.ReadFile("/var/run/secrets/kubernetes.io/serviceaccount/ca.crt")
	host := os.Getenv("KUBERNETES_SERVICE_HOST")
	port := os.Getenv("KUBERNETES_SERVICE_PORT")

	if host == "" {
		host = "kubernetes.default.svc"
	}
	if port == "" {
		port = "443"
	}

	return &KubernetesCollector{
		client: &http.Client{
			Timeout: 10 * time.Second,
			Transport: &http.Transport{
				TLSClientConfig: nil, // Will use CA cert if available
			},
		},
		inCluster: len(token) > 0,
		token:     string(token),
		caCert:    caCert,
		host:      fmt.Sprintf("https://%s:%s", host, port),
	}
}

func (c *KubernetesCollector) IsAvailable() bool {
	if !c.inCluster {
		// Try kubectl
		return c.tryKubectl()
	}
	return true
}

func (c *KubernetesCollector) tryKubectl() bool {
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, "GET", c.host+"/api/v1/namespaces", nil)
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

func (c *KubernetesCollector) makeRequest(path string) ([]byte, error) {
	url := c.host + path

	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, err
	}

	if c.token != "" {
		req.Header.Set("Authorization", "Bearer "+c.token)
	}

	resp, err := c.client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("API returned status %d", resp.StatusCode)
	}

	return io.ReadAll(resp.Body)
}

func (c *KubernetesCollector) GetClusterVersion() (string, error) {
	data, err := c.makeRequest("/version")
	if err != nil {
		return "", err
	}

	var version struct {
		GitVersion string `json:"gitVersion"`
	}
	if err := json.Unmarshal(data, &version); err != nil {
		return "", err
	}

	return version.GitVersion, nil
}

func (c *KubernetesCollector) ListNodes() ([]NodeInfo, error) {
	data, err := c.makeRequest("/api/v1/nodes")
	if err != nil {
		return nil, err
	}

	var nodeList struct {
		Items []NodeInfo `json:"items"`
	}
	if err := json.Unmarshal(data, &nodeList); err != nil {
		return nil, err
	}

	return nodeList.Items, nil
}

func (c *KubernetesCollector) ListPods(namespace string) ([]PodInfo, error) {
	path := "/api/v1/pods"
	if namespace != "" {
		path = fmt.Sprintf("/api/v1/namespaces/%s/pods", namespace)
	}

	data, err := c.makeRequest(path)
	if err != nil {
		return nil, err
	}

	var podList struct {
		Items []PodInfo `json:"items"`
	}
	if err := json.Unmarshal(data, &podList); err != nil {
		return nil, err
	}

	return podList.Items, nil
}

func (c *KubernetesCollector) ListDeployments(namespace string) ([]DeploymentInfo, error) {
	path := "/apis/apps/v1/deployments"
	if namespace != "" {
		path = fmt.Sprintf("/apis/apps/v1/namespaces/%s/deployments", namespace)
	}

	data, err := c.makeRequest(path)
	if err != nil {
		return nil, err
	}

	var deploymentList struct {
		Items []DeploymentInfo `json:"items"`
	}
	if err := json.Unmarshal(data, &deploymentList); err != nil {
		return nil, err
	}

	return deploymentList.Items, nil
}

func (c *KubernetesCollector) ListNamespaces() ([]NamespaceInfo, error) {
	data, err := c.makeRequest("/api/v1/namespaces")
	if err != nil {
		return nil, err
	}

	var namespaceList struct {
		Items []NamespaceInfo `json:"items"`
	}
	if err := json.Unmarshal(data, &namespaceList); err != nil {
		return nil, err
	}

	return namespaceList.Items, nil
}

func (c *KubernetesCollector) CollectKubernetesMetrics() (map[string]interface{}, error) {
	metrics := make(map[string]interface{})

	// Get cluster version
	version, err := c.GetClusterVersion()
	if err != nil {
		return nil, err
	}
	metrics["cluster_version"] = version

	// Get nodes
	nodes, err := c.ListNodes()
	if err != nil {
		return nil, err
	}

	var nodeList []map[string]interface{}
	totalCPU := 0
	totalMemory := int64(0)
	readyNodes := 0

	for _, node := range nodes {
		nodeInfo := map[string]interface{}{
			"name":           node.Metadata.Name,
			"os":             node.Status.NodeInfo.OperatingSystem,
			"arch":           node.Status.NodeInfo.Architecture,
			"kubelet_version": node.Status.NodeInfo.KubeletVersion,
			"capacity_cpu":   node.Status.Capacity.CPU,
			"capacity_memory": node.Status.Capacity.Memory,
		}

		// Check node status
		for _, addr := range node.Status.Addresses {
			if addr.Type == "InternalIP" {
				nodeInfo["ip"] = addr.Address
			}
		}

		for _, condition := range node.Status.Conditions {
			if condition.Type == "Ready" {
				nodeInfo["ready"] = condition.Status == "True"
				if condition.Status == "True" {
					readyNodes++
				}
			}
		}

		// Parse CPU (millicores)
		if cpuStr := node.Status.Capacity.CPU; cpuStr != "" {
			if strings.HasSuffix(cpuStr, "m") {
				var cpu int
				fmt.Sscanf(cpuStr, "%dm", &cpu)
				totalCPU += cpu
			} else {
				var cpu int
				fmt.Sscanf(cpuStr, "%d", &cpu)
				totalCPU += cpu * 1000
			}
		}

		nodeList = append(nodeList, nodeInfo)
	}

	metrics["nodes"] = nodeList
	metrics["node_summary"] = map[string]interface{}{
		"total":      len(nodes),
		"ready":      readyNodes,
		"not_ready":  len(nodes) - readyNodes,
		"total_cpu":  totalCPU,
	}

	// Get pods
	pods, err := c.ListPods("")
	if err != nil {
		return nil, err
	}

	podStatus := map[string]int{
		"running":  0,
		"pending":  0,
		"succeeded": 0,
		"failed":   0,
		"unknown":  0,
	}

	failedPods := []map[string]interface{}{}

	for _, pod := range pods {
		podStatus[strings.ToLower(pod.Status.Phase)]++

		// Check for failed pods
		if pod.Status.Phase == "Failed" || pod.Status.Phase == "CrashLoopBackOff" {
			failedPods = append(failedPods, map[string]interface{}{
				"name":      pod.Metadata.Name,
				"namespace": pod.Metadata.Namespace,
				"phase":     pod.Status.Phase,
				"node":      pod.Spec.NodeName,
			})
		}

		// Check container restarts
		for _, cs := range pod.Status.ContainerStatuses {
			if cs.RestartCount > 5 {
				failedPods = append(failedPods, map[string]interface{}{
					"name":           pod.Metadata.Name,
					"namespace":      pod.Metadata.Namespace,
					"container":      cs.Name,
					"restart_count":  cs.RestartCount,
				})
			}
		}
	}

	metrics["pods"] = map[string]interface{}{
		"total":   len(pods),
		"status":  podStatus,
		"failed":  failedPods,
	}

	// Get deployments
	deployments, err := c.ListDeployments("")
	if err != nil {
		return nil, err
	}

	var deploymentList []map[string]interface{}
	deploymentIssues := []map[string]interface{}{}

	for _, deploy := range deployments {
		deployInfo := map[string]interface{}{
			"name":             deploy.Metadata.Name,
			"namespace":        deploy.Metadata.Namespace,
			"desired_replicas": deploy.Spec.Replicas,
			"ready_replicas":   deploy.Status.ReadyReplicas,
			"available":        deploy.Status.AvailableReplicas,
		}

		deploymentList = append(deploymentList, deployInfo)

		// Check for deployment issues
		if deploy.Status.UnavailableReplicas > 0 {
			deploymentIssues = append(deploymentIssues, map[string]interface{}{
				"name":            deploy.Metadata.Name,
				"namespace":       deploy.Metadata.Namespace,
				"unavailable":     deploy.Status.UnavailableReplicas,
			})
		}
	}

	metrics["deployments"] = map[string]interface{}{
		"total":  len(deployments),
		"list":   deploymentList,
		"issues": deploymentIssues,
	}

	// Get namespaces
	namespaces, err := c.ListNamespaces()
	if err == nil {
		var nsList []map[string]interface{}
		for _, ns := range namespaces {
			nsList = append(nsList, map[string]interface{}{
				"name":  ns.Metadata.Name,
				"phase": ns.Status.Phase,
			})
		}
		metrics["namespaces"] = nsList
	}

	return metrics, nil
}
