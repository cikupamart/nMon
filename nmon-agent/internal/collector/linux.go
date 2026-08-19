package collector

import (
	"bufio"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"time"
)

type LinuxCollector struct{}

func NewLinuxCollector() *LinuxCollector {
	return &LinuxCollector{}
}

func (c *LinuxCollector) Collect() (map[string]interface{}, error) {
	data := make(map[string]interface{})

	hostname, _ := os.Hostname()
	data["hostname"] = hostname
	data["os"] = runtime.GOOS
	data["arch"] = runtime.GOARCH
	data["timestamp"] = time.Now().UTC().Format(time.RFC3339)

	osInfo, _ := c.GetOSInfo()
	data["os_info"] = osInfo

	cpuInfo, _ := c.GetCPUInfo()
	data["cpu"] = cpuInfo

	memInfo, _ := c.GetMemoryInfo()
	data["memory"] = memInfo

	diskInfo, _ := c.GetDiskInfo()
	data["disk"] = diskInfo

	netInfo, _ := c.GetNetworkInfo()
	data["network"] = netInfo

	procInfo, _ := c.GetProcessInfo()
	data["processes"] = procInfo

	loadInfo := c.getLoadInfo()
	data["load"] = loadInfo

	uptime := c.getUptime()
	data["uptime"] = uptime

	return data, nil
}

func (c *LinuxCollector) GetOSInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Read /etc/os-release
	if file, err := os.Open("/etc/os-release"); err == nil {
		defer file.Close()
		scanner := bufio.NewScanner(file)
		for scanner.Scan() {
			line := scanner.Text()
			if parts := strings.SplitN(line, "=", 2); len(parts) == 2 {
				key := strings.ToLower(parts[0])
				value := strings.Trim(parts[1], "\"")
				info[key] = value
			}
		}
	}

	// Get kernel version
	if data, err := os.ReadFile("/proc/version"); err == nil {
		info["kernel"] = strings.TrimSpace(string(data))
	}

	return info, nil
}

func (c *LinuxCollector) GetCPUInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Read /proc/cpuinfo
	file, err := os.Open("/proc/cpuinfo")
	if err != nil {
		return nil, err
	}
	defer file.Close()

	cores := 0
	model := ""
	speed := 0.0

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		if strings.HasPrefix(line, "model name") {
			if parts := strings.SplitN(line, ":", 2); len(parts) == 2 {
				model = strings.TrimSpace(parts[1])
			}
		}
		if strings.HasPrefix(line, "cpu MHz") {
			if parts := strings.SplitN(line, ":", 2); len(parts) == 2 {
				if v, err := strconv.ParseFloat(strings.TrimSpace(parts[1]), 64); err == nil {
					speed = v
				}
			}
		}
		if strings.HasPrefix(line, "processor") {
			cores++
		}
	}

	info["model"] = model
	info["cores"] = cores
	info["speed"] = speed

	// Get CPU usage from /proc/stat
	usage, err := c.getCPUUsage()
	if err == nil {
		info["usage"] = usage["total"]
		info["user"] = usage["user"]
		info["system"] = usage["system"]
		info["idle"] = usage["idle"]
		info["iowait"] = usage["iowait"]
	}

	return info, nil
}

func (c *LinuxCollector) getCPUUsage() (map[string]float64, error) {
	data, err := os.ReadFile("/proc/stat")
	if err != nil {
		return nil, err
	}

	lines := strings.Split(string(data), "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "cpu ") {
			fields := strings.Fields(line)
			if len(fields) >= 5 {
				user, _ := strconv.ParseFloat(fields[1], 64)
				system, _ := strconv.ParseFloat(fields[3], 64)
				idle, _ := strconv.ParseFloat(fields[4], 64)
				iowait := 0.0
				if len(fields) > 5 {
					iowait, _ = strconv.ParseFloat(fields[5], 64)
				}

				total := user + system + idle + iowait
				if total == 0 {
					total = 1
				}

				return map[string]float64{
					"user":   (user / total) * 100,
					"system": (system / total) * 100,
					"idle":   (idle / total) * 100,
					"iowait": (iowait / total) * 100,
					"total":  ((total - idle) / total) * 100,
				}, nil
			}
		}
	}

	return nil, fmt.Errorf("cpu stat not found")
}

func (c *LinuxCollector) GetMemoryInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	file, err := os.Open("/proc/meminfo")
	if err != nil {
		return nil, err
	}
	defer file.Close()

	memTotal := uint64(0)
	memFree := uint64(0)
	memAvailable := uint64(0)
	buffers := uint64(0)
	cached := uint64(0)
	swapTotal := uint64(0)
	swapFree := uint64(0)

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) < 2 {
			continue
		}

		value, _ := strconv.ParseUint(fields[1], 10, 64)

		switch fields[0] {
		case "MemTotal:":
			memTotal = value
		case "MemFree:":
			memFree = value
		case "MemAvailable:":
			memAvailable = value
		case "Buffers:":
			buffers = value
		case "Cached:":
			cached = value
		case "SwapTotal:":
			swapTotal = value
		case "SwapFree:":
			swapFree = value
		}
	}

	used := memTotal - memFree - buffers - cached
	var usage float64
	if memTotal > 0 {
		usage = (float64(used) / float64(memTotal)) * 100
	}

	info["total"] = memTotal * 1024 // Convert to bytes
	info["free"] = memFree * 1024
	info["used"] = used * 1024
	info["available"] = memAvailable * 1024
	info["buffers"] = buffers * 1024
	info["cached"] = cached * 1024
	info["swap_total"] = swapTotal * 1024
	info["swap_used"] = (swapTotal - swapFree) * 1024
	info["usage"] = usage

	return info, nil
}

func (c *LinuxCollector) GetDiskInfo() ([]map[string]interface{}, error) {
	var disks []map[string]interface{}

	// Use df command for more accurate disk info
	cmd := exec.Command("df", "-B1", "-T")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	lines := strings.Split(string(output), "\n")
	for i, line := range lines {
		if i == 0 || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 7 {
			continue
		}

		// Skip non-physical filesystems
		fsType := fields[1]
		if fsType == "tmpfs" || fsType == "devtmpfs" || fsType == "squashfs" || 
		   strings.HasPrefix(fsType, "overlay") || strings.HasPrefix(fields[0], "/dev/loop") {
			continue
		}

		total, _ := strconv.ParseUint(fields[2], 10, 64)
		used, _ := strconv.ParseUint(fields[3], 10, 64)
		free, _ := strconv.ParseUint(fields[4], 10, 64)

		var usage float64
		if total > 0 {
			usage = (float64(used) / float64(total)) * 100
		}

		disks = append(disks, map[string]interface{}{
			"device":     fields[0],
			"fs_type":    fsType,
			"mount_point": fields[6],
			"total":      total,
			"used":       used,
			"free":       free,
			"usage":      usage,
		})
	}

	return disks, nil
}

func (c *LinuxCollector) GetNetworkInfo() ([]map[string]interface{}, error) {
	var networks []map[string]interface{}

	// Read /proc/net/dev
	file, err := os.Open("/proc/net/dev")
	if err != nil {
		return nil, err
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	lineNum := 0
	for scanner.Scan() {
		lineNum++
		if lineNum <= 2 {
			continue // Skip headers
		}

		line := scanner.Text()
		parts := strings.SplitN(line, ":", 2)
		if len(parts) < 2 {
			continue
		}

		ifName := strings.TrimSpace(parts[0])
		if ifName == "lo" {
			continue // Skip loopback
		}

		fields := strings.Fields(parts[1])
		if len(fields) < 10 {
			continue
		}

		rxBytes, _ := strconv.ParseUint(fields[0], 10, 64)
		rxPackets, _ := strconv.ParseUint(fields[1], 10, 64)
		rxErrors, _ := strconv.ParseUint(fields[2], 10, 64)
		txBytes, _ := strconv.ParseUint(fields[8], 10, 64)
		txPackets, _ := strconv.ParseUint(fields[9], 10, 64)
		txErrors, _ := strconv.ParseUint(fields[10], 10, 64)

		// Get IP address
		ipAddr := c.getIPForInterface(ifName)

		networks = append(networks, map[string]interface{}{
			"interface": ifName,
			"ip_address": ipAddr,
			"rx_bytes":   rxBytes,
			"tx_bytes":   txBytes,
			"rx_packets": rxPackets,
			"tx_packets": txPackets,
			"rx_errors":  rxErrors,
			"tx_errors":  txErrors,
		})
	}

	return networks, nil
}

func (c *LinuxCollector) getIPForInterface(ifName string) string {
	path := filepath.Join("/sys/class/net", ifName, "operstate")
	data, err := os.ReadFile(path)
	if err != nil || strings.TrimSpace(string(data)) != "up" {
		return ""
	}

	// Try to get IP via ip command
	cmd := exec.Command("ip", "-4", "addr", "show", ifName)
	output, err := cmd.Output()
	if err != nil {
		return ""
	}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		if strings.Contains(line, "inet ") {
			fields := strings.Fields(line)
			for i, field := range fields {
				if field == "inet" && i+1 < len(fields) {
					ip := fields[i+1]
					if idx := strings.Index(ip, "/"); idx != -1 {
						return ip[:idx]
					}
					return ip
				}
			}
		}
	}

	return ""
}

func (c *LinuxCollector) GetProcessInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Get process count from /proc
	entries, err := os.ReadDir("/proc")
	if err != nil {
		return nil, err
	}

	total := 0
	for _, entry := range entries {
		if _, err := strconv.Atoi(entry.Name()); err == nil {
			total++
		}
	}

	info["total"] = total

	// Get top processes by CPU using ps command
	cmd := exec.Command("ps", "aux", "--sort=-pcpu")
	output, err := cmd.Output()
	if err != nil {
		return info, nil
	}

	var topProcesses []map[string]interface{}
	lines := strings.Split(string(output), "\n")
	for i, line := range lines {
		if i == 0 || i > 10 { // Skip header and limit to top 10
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 11 {
			continue
		}

		pid, _ := strconv.Atoi(fields[1])
		cpu, _ := strconv.ParseFloat(fields[2], 64)
		mem, _ := strconv.ParseFloat(fields[3], 64)

		topProcesses = append(topProcesses, map[string]interface{}{
			"pid":    pid,
			"user":   fields[0],
			"cpu":    cpu,
			"memory": mem,
			"command": strings.Join(fields[10:], " "),
		})
	}

	info["top"] = topProcesses

	return info, nil
}

func (c *LinuxCollector) getLoadInfo() map[string]interface{} {
	data, err := os.ReadFile("/proc/loadavg")
	if err != nil {
		return nil
	}

	fields := strings.Fields(string(data))
	if len(fields) < 3 {
		return nil
	}

	load1, _ := strconv.ParseFloat(fields[0], 64)
	load5, _ := strconv.ParseFloat(fields[1], 64)
	load15, _ := strconv.ParseFloat(fields[2], 64)

	return map[string]interface{}{
		"load1":  load1,
		"load5":  load5,
		"load15": load15,
	}
}

func (c *LinuxCollector) getUptime() int64 {
	data, err := os.ReadFile("/proc/uptime")
	if err != nil {
		return 0
	}

	fields := strings.Fields(string(data))
	if len(fields) < 1 {
		return 0
	}

	uptime, _ := strconv.ParseFloat(fields[0], 64)
	return int64(uptime)
}
