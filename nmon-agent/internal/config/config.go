package config

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"runtime"
)

type Config struct {
	ServerKey string `json:"server_key"`
	Gateway   string `json:"gateway"`
	Interval  int    `json:"interval"`
	LogLevel  string `json:"log_level"`
	DataDir   string `json:"data_dir"`
	RemotePort int   `json:"remote_port"`
	EnableRemote bool `json:"enable_remote"`
}

func Load(configPath, serverKey, gateway string, interval int) (*Config, error) {
	cfg := &Config{
		ServerKey:    serverKey,
		Gateway:      gateway,
		Interval:     interval,
		LogLevel:     "info",
		RemotePort:   9100,
		EnableRemote: true,
	}

	// Try to load from file
	if configPath == "" {
		configPath = getDefaultConfigPath()
	}

	if _, err := os.Stat(configPath); err == nil {
		if err := cfg.loadFromFile(configPath); err != nil {
			return nil, fmt.Errorf("failed to load config file: %w", err)
		}
	}

	// Command line flags override config file
	if serverKey != "" {
		cfg.ServerKey = serverKey
	}
	if gateway != "" {
		cfg.Gateway = gateway
	}
	if interval > 0 {
		cfg.Interval = interval
	}

	// Validate
	if cfg.ServerKey == "" {
		return nil, fmt.Errorf("server key is required")
	}
	if cfg.Gateway == "" {
		return nil, fmt.Errorf("gateway URL is required")
	}
	if cfg.Interval < 10 {
		cfg.Interval = 10
	}

	return cfg, nil
}

func getDefaultConfigPath() string {
	if runtime.GOOS == "windows" {
		programData := os.Getenv("ProgramData")
		if programData == "" {
			programData = "C:\\ProgramData"
		}
		return filepath.Join(programData, "nmon-agent", "config.json")
	}
	return "/opt/nmon/config.json"
}

func (c *Config) loadFromFile(path string) error {
	data, err := os.ReadFile(path)
	if err != nil {
		return err
	}
	return json.Unmarshal(data, c)
}

func (c *Config) Save(path string) error {
	if path == "" {
		path = getDefaultConfigPath()
	}

	dir := filepath.Dir(path)
	if err := os.MkdirAll(dir, 0755); err != nil {
		return err
	}

	data, err := json.MarshalIndent(c, "", "  ")
	if err != nil {
		return err
	}

	return os.WriteFile(path, data, 0644)
}
