package collector

import (
	"time"
)

// Collector defines the interface for system metrics collection
type Collector interface {
	Collect() (map[string]interface{}, error)
	GetOSInfo() (map[string]interface{}, error)
	GetCPUInfo() (map[string]interface{}, error)
	GetMemoryInfo() (map[string]interface{}, error)
	GetDiskInfo() (map[string]interface{}, error)
	GetNetworkInfo() (map[string]interface{}, error)
	GetProcessInfo() (map[string]interface{}, error)
}

// SystemMetrics holds all collected system metrics
type SystemMetrics struct {
	Timestamp   time.Time              `json:"timestamp"`
	Hostname    string                 `json:"hostname"`
	OS          string                 `json:"os"`
	Arch        string                 `json:"arch"`
	Kernel      string                 `json:"kernel"`
	Uptime      int64                  `json:"uptime"`
	CPU         CPUMetrics             `json:"cpu"`
	Memory      MemoryMetrics          `json:"memory"`
	Disk        []DiskMetrics          `json:"disk"`
	Network     []NetworkMetrics       `json:"network"`
	Processes   ProcessMetrics         `json:"processes"`
	Load        LoadMetrics            `json:"load"`
}

type CPUMetrics struct {
	Model       string    `json:"model"`
	Cores       int       `json:"cores"`
	Speed       float64   `json:"speed"`
	Usage       float64   `json:"usage"`
	User        float64   `json:"user"`
	System      float64   `json:"system"`
	Idle        float64   `json:"idle"`
	IOWait      float64   `json:"iowait"`
	Steal       float64   `json:"steal"`
	Temperature float64   `json:"temperature,omitempty"`
}

type MemoryMetrics struct {
	Total     uint64  `json:"total"`
	Free      uint64  `json:"free"`
	Used      uint64  `json:"used"`
	Available uint64  `json:"available"`
	Buffers   uint64  `json:"buffers"`
	Cached    uint64  `json:"cached"`
	SwapTotal uint64  `json:"swap_total"`
	SwapUsed  uint64  `json:"swap_used"`
	Usage     float64 `json:"usage"`
}

type DiskMetrics struct {
	MountPoint string  `json:"mount_point"`
	Device     string  `json:"device"`
	FSType     string  `json:"fs_type"`
	Total      uint64  `json:"total"`
	Used       uint64  `json:"used"`
	Free       uint64  `json:"free"`
	Usage      float64 `json:"usage"`
	Inodes     int64   `json:"inodes,omitempty"`
	InodesFree int64   `json:"inodes_free,omitempty"`
}

type NetworkMetrics struct {
	Interface string `json:"interface"`
	IPAddress string `json:"ip_address"`
	MAC       string `json:"mac"`
	RxBytes   uint64 `json:"rx_bytes"`
	TxBytes   uint64 `json:"tx_bytes"`
	RxPackets uint64 `json:"rx_packets"`
	TxPackets uint64 `json:"tx_packets"`
	RxErrors  uint64 `json:"rx_errors"`
	TxErrors  uint64 `json:"tx_errors"`
	Speed     int    `json:"speed"`
	Up        bool   `json:"up"`
}

type ProcessMetrics struct {
	Total     int            `json:"total"`
	Running   int            `json:"running"`
	Sleeping  int            `json:"sleeping"`
	Zombie    int            `json:"zombie"`
	Top       []ProcessInfo  `json:"top"`
}

type ProcessInfo struct {
	PID       int     `json:"pid"`
	Name      string  `json:"name"`
	User      string  `json:"user"`
	CPU       float64 `json:"cpu"`
	Memory    float64 `json:"memory"`
	Status    string  `json:"status"`
	StartTime string  `json:"start_time"`
}

type LoadMetrics struct {
	Load1  float64 `json:"load1"`
	Load5  float64 `json:"load5"`
	Load15 float64 `json:"load15"`
}
