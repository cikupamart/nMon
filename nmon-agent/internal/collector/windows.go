package collector

import (
	"fmt"
	"os/exec"
	"runtime"
	"strconv"
	"strings"
	"time"
)

type WindowsCollector struct{}

func NewWindowsCollector() *WindowsCollector {
	return &WindowsCollector{}
}

func (c *WindowsCollector) Collect() (map[string]interface{}, error) {
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

func (c *WindowsCollector) GetOSInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Get OS version using PowerShell
	cmd := exec.Command("powershell", "-Command", "Get-CimInstance Win32_OperatingSystem | Select-Object Caption, Version, BuildNumber, OSArchitecture | ConvertTo-Json")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	// Parse JSON output (simplified)
	outputStr := string(output)
	info["caption"] = c.extractJSONValue(outputStr, "Caption")
	info["version"] = c.extractJSONValue(outputStr, "Version")
	info["build"] = c.extractJSONValue(outputStr, "BuildNumber")
	info["architecture"] = c.extractJSONValue(outputStr, "OSArchitecture")

	return info, nil
}

func (c *WindowsCollector) GetCPUInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Get CPU info using PowerShell
	cmd := exec.Command("powershell", "-Command", "Get-CimInstance Win32_Processor | Select-Object Name, NumberOfCores, MaxClockSpeed, LoadPercentage | ConvertTo-Json")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	outputStr := string(output)
	info["model"] = c.extractJSONValue(outputStr, "Name")
	cores, _ := strconv.Atoi(c.extractJSONValue(outputStr, "NumberOfCores"))
	info["cores"] = cores
	speed, _ := strconv.ParseFloat(c.extractJSONValue(outputStr, "MaxClockSpeed"), 64)
	info["speed"] = speed / 1000 // Convert MHz to GHz
	usage, _ := strconv.ParseFloat(c.extractJSONValue(outputStr, "LoadPercentage"), 64)
	info["usage"] = usage

	return info, nil
}

func (c *WindowsCollector) GetMemoryInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Get memory info using PowerShell
	cmd := exec.Command("powershell", "-Command", "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory, TotalVirtualMemorySize, FreeVirtualMemory | ConvertTo-Json")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	outputStr := string(output)
	totalKB, _ := strconv.ParseUint(c.extractJSONValue(outputStr, "TotalVisibleMemorySize"), 10, 64)
	freeKB, _ := strconv.ParseUint(c.extractJSONValue(outputStr, "FreePhysicalMemory"), 10, 64)
	swapTotalKB, _ := strconv.ParseUint(c.extractJSONValue(outputStr, "TotalVirtualMemorySize"), 10, 64)
	swapFreeKB, _ := strconv.ParseUint(c.extractJSONValue(outputStr, "FreeVirtualMemory"), 10, 64)

	usedKB := totalKB - freeKB
	var usage float64
	if totalKB > 0 {
		usage = (float64(usedKB) / float64(totalKB)) * 100
	}

	info["total"] = totalKB * 1024 // Convert to bytes
	info["free"] = freeKB * 1024
	info["used"] = usedKB * 1024
	info["swap_total"] = swapTotalKB * 1024
	info["swap_used"] = (swapTotalKB - swapFreeKB) * 1024
	info["usage"] = usage

	return info, nil
}

func (c *WindowsCollector) GetDiskInfo() ([]map[string]interface{}, error) {
	var disks []map[string]interface{}

	// Get disk info using PowerShell
	cmd := exec.Command("powershell", "-Command", "Get-CimInstance Win32_LogicalDisk | Where-Object {$_.DriveType -eq 3} | Select-Object DeviceID, FileSystem, Size, FreeSpace, VolumeName | ConvertTo-Json")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	// Parse JSON array (simplified parser)
	outputStr := string(output)
	// This is a simplified parser - in production, use encoding/json
	lines := strings.Split(outputStr, "\n")
	currentDisk := make(map[string]interface{})

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.Contains(line, "DeviceID") {
			if len(currentDisk) > 0 {
				disks = append(disks, currentDisk)
				currentDisk = make(map[string]interface{})
			}
			currentDisk["device"] = c.extractValue(line)
		} else if strings.Contains(line, "FileSystem") {
			currentDisk["fs_type"] = c.extractValue(line)
		} else if strings.Contains(line, "Size") {
			size, _ := strconv.ParseUint(c.extractValue(line), 10, 64)
			currentDisk["total"] = size
		} else if strings.Contains(line, "FreeSpace") {
			free, _ := strconv.ParseUint(c.extractValue(line), 10, 64)
			currentDisk["free"] = free
		}
	}

	if len(currentDisk) > 0 {
		disks = append(disks, currentDisk)
	}

	// Calculate usage for each disk
	for _, disk := range disks {
		total, _ := disk["total"].(uint64)
		free, _ := disk["free"].(uint64)
		used := total - free
		disk["used"] = used

		var usage float64
		if total > 0 {
			usage = (float64(used) / float64(total)) * 100
		}
		disk["usage"] = usage
	}

	return disks, nil
}

func (c *WindowsCollector) GetNetworkInfo() ([]map[string]interface{}, error) {
	var networks []map[string]interface{}

	// Get network info using PowerShell
	cmd := exec.Command("powershell", "-Command", "Get-NetAdapter | Where-Object {$_.Status -eq 'Up'} | Select-Object Name, InterfaceDescription, MacAddress, LinkSpeed, ifIndex | ConvertTo-Json")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	// Get network statistics
	statsCmd := exec.Command("powershell", "-Command", "Get-NetAdapterStatistics | Select-Object Name, ReceivedBytes, SentBytes, ReceivedPackets, SentPackets | ConvertTo-Json")
	statsOutput, err := statsCmd.Output()
	if err == nil {
		// Parse and merge stats with adapter info
		// Simplified implementation
	}

	outputStr := string(output)
	lines := strings.Split(outputStr, "\n")
	currentNet := make(map[string]interface{})

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.Contains(line, "Name") && !strings.Contains(line, "InterfaceDescription") {
			if len(currentNet) > 0 {
				networks = append(networks, currentNet)
				currentNet = make(map[string]interface{})
			}
			currentNet["interface"] = c.extractValue(line)
		} else if strings.Contains(line, "MacAddress") {
			currentNet["mac"] = c.extractValue(line)
		} else if strings.Contains(line, "LinkSpeed") {
			currentNet["speed"] = c.extractValue(line)
		}
	}

	if len(currentNet) > 0 {
		networks = append(networks, currentNet)
	}

	return networks, nil
}

func (c *WindowsCollector) GetProcessInfo() (map[string]interface{}, error) {
	info := make(map[string]interface{})

	// Get process count
	cmd := exec.Command("powershell", "-Command", "(Get-Process).Count")
	output, err := cmd.Output()
	if err != nil {
		return nil, err
	}

	total, _ := strconv.Atoi(strings.TrimSpace(string(output)))
	info["total"] = total

	// Get top processes by CPU
	topCmd := exec.Command("powershell", "-Command", "Get-Process | Sort-Object CPU -Descending | Select-Object -First 10 Id, ProcessName, CPU, WorkingSet64, StartTime | ConvertTo-Json")
	topOutput, err := topCmd.Output()
	if err == nil {
		// Parse top processes (simplified)
		info["top"] = []map[string]interface{}{} // Placeholder
	}

	return info, nil
}

func (c *WindowsCollector) getLoadInfo() map[string]interface{} {
	// Windows doesn't have traditional load average
	// Use CPU queue length as alternative
	cmd := exec.Command("powershell", "-Command", "Get-Counter '\\System\\Processor Queue Length' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue")
	output, err := cmd.Output()
	if err != nil {
		return nil
	}

	queueLength, _ := strconv.ParseFloat(strings.TrimSpace(string(output)), 64)

	return map[string]interface{}{
		"queue_length": queueLength,
	}
}

func (c *WindowsCollector) getUptime() int64 {
	cmd := exec.Command("powershell", "-Command", "(Get-Date) - (Get-CimInstance Win32_OperatingSystem).LastBootUpTime | Select-Object -ExpandProperty TotalSeconds")
	output, err := cmd.Output()
	if err != nil {
		return 0
	}

	seconds, _ := strconv.ParseFloat(strings.TrimSpace(string(output)), 64)
	return int64(seconds)
}

// Helper functions
func (c *WindowsCollector) extractJSONValue(json, key string) string {
	search := fmt.Sprintf(`"%s":`, key)
	idx := strings.Index(json, search)
	if idx == -1 {
		return ""
	}

	rest := json[idx+len(search):]
	rest = strings.TrimSpace(rest)

	// Find value (string or number)
	if strings.HasPrefix(rest, `"`) {
		end := strings.Index(rest[1:], `"`)
		if end != -1 {
			return rest[1 : end+1]
		}
	} else {
		end := strings.IndexAny(rest, ",}\n")
		if end != -1 {
			return strings.TrimSpace(rest[:end])
		}
	}

	return rest
}

func (c *WindowsCollector) extractValue(line string) string {
	if idx := strings.Index(line, ":"); idx != -1 {
		value := line[idx+1:]
		value = strings.Trim(value, ` "`)
		value = strings.TrimSuffix(value, ",")
		return value
	}
	return ""
}
