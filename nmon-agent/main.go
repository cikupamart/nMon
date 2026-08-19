package main

import (
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"runtime"
	"syscall"
	"time"

	"nmon-agent/internal/collector"
	"nmon-agent/internal/config"
	"nmon-agent/internal/remote"
	"nmon-agent/internal/sender"
	"nmon-agent/internal/updater"
)

var (
	Version   = "2.0.0"
	BuildTime = "unknown"
	UpdateURL = "https://releases.nmon.dev"
)

func main() {
	// Parse command line flags
	configPath := flag.String("config", "", "Path to config file")
	showVersion := flag.Bool("version", false, "Show version")
	serverKey := flag.String("server-key", "", "Server authentication key")
	gateway := flag.String("gateway", "", "Gateway server URL")
	interval := flag.Int("interval", 60, "Collection interval in seconds")
	enableDocker := flag.Bool("enable-docker", true, "Enable Docker monitoring")
	enableKubernetes := flag.Bool("enable-kubernetes", true, "Enable Kubernetes monitoring")
	enableAutoUpdate := flag.Bool("enable-autoupdate", true, "Enable auto-update")
	forceUpdate := flag.Bool("force-update", false, "Force update check")
	rollback := flag.Bool("rollback", false, "Rollback to previous version")
	flag.Parse()

	if *showVersion {
		fmt.Printf("nMon Agent v%s (built %s) - %s/%s\n", Version, BuildTime, runtime.GOOS, runtime.GOARCH)
		os.Exit(0)
	}

	// Handle rollback
	if *rollback {
		u := updater.NewUpdater(Version, UpdateURL)
		if err := u.Rollback(); err != nil {
			log.Fatalf("Rollback failed: %v", err)
		}
		fmt.Println("✅ Rollback completed. Please restart the agent.")
		os.Exit(0)
	}

	// Load configuration
	cfg, err := config.Load(*configPath, *serverKey, *gateway, *interval)
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	// Create collector based on OS
	var sysCollector collector.Collector
	switch runtime.GOOS {
	case "linux":
		sysCollector = collector.NewLinuxCollector()
	case "windows":
		sysCollector = collector.NewWindowsCollector()
	default:
		log.Fatalf("Unsupported OS: %s", runtime.GOOS)
	}

	// Check Docker availability
	var dockerCollector *collector.DockerCollector
	if *enableDocker {
		dockerCollector = collector.NewDockerCollector()
		if dockerCollector.IsAvailable() {
			log.Println("✅ Docker monitoring enabled")
		} else {
			log.Println("⚠️ Docker not available, skipping Docker monitoring")
			dockerCollector = nil
		}
	}

	// Check Kubernetes availability
	var k8sCollector *collector.KubernetesCollector
	if *enableKubernetes {
		k8sCollector = collector.NewKubernetesCollector()
		if k8sCollector.IsAvailable() {
			log.Println("✅ Kubernetes monitoring enabled")
		} else {
			log.Println("⚠️ Kubernetes not available, skipping K8s monitoring")
			k8sCollector = nil
		}
	}

	// Create sender
	dataSender := sender.NewHTTPSender(cfg.Gateway, cfg.ServerKey)

	// Create remote control handler
	remoteHandler := remote.NewHandler(cfg, sysCollector)

	// Setup signal handling
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	// Start auto-update if enabled
	var updateDone chan struct{}
	if *enableAutoUpdate {
		updateDone = make(chan struct{})
		u := updater.NewUpdater(Version, UpdateURL)
		
		if *forceUpdate {
			go func() {
				if err := u.ForceUpdate(); err != nil {
					log.Printf("Force update failed: %v", err)
				}
			}()
		}
		
		go u.StartAutoUpdate(updateDone)
	}

	// Start remote control listener
	go remoteHandler.Start()

	// Collection loop
	ticker := time.NewTicker(time.Duration(cfg.Interval) * time.Second)
	defer ticker.Stop()

	// Initial collection
	collectAndSend(sysCollector, dockerCollector, k8sCollector, dataSender, cfg)

	// Start periodic collection
	go func() {
		for range ticker.C {
			collectAndSend(sysCollector, dockerCollector, k8sCollector, dataSender, cfg)
		}
	}()

	log.Printf("nMon Agent v%s started on %s/%s", Version, runtime.GOOS, runtime.GOARCH)
	log.Printf("Gateway: %s, Interval: %ds", cfg.Gateway, cfg.Interval)

	// Wait for signal
	<-sigChan
	log.Println("Shutting down agent...")
	remoteHandler.Stop()
	if updateDone != nil {
		close(updateDone)
	}
}

func collectAndSend(
	sysCollector collector.Collector,
	dockerCollector *collector.DockerCollector,
	k8sCollector *collector.KubernetesCollector,
	dataSender sender.Sender,
	cfg *config.Config,
) {
	data, err := sysCollector.Collect()
	if err != nil {
		log.Printf("System collection error: %v", err)
		return
	}

	// Add Docker metrics if available
	if dockerCollector != nil {
		dockerMetrics, err := dockerCollector.CollectDockerMetrics()
		if err == nil {
			data["docker"] = dockerMetrics
		} else {
			log.Printf("Docker collection error: %v", err)
		}
	}

	// Add Kubernetes metrics if available
	if k8sCollector != nil {
		k8sMetrics, err := k8sCollector.CollectKubernetesMetrics()
		if err == nil {
			data["kubernetes"] = k8sMetrics
		} else {
			log.Printf("Kubernetes collection error: %v", err)
		}
	}

	// Add metadata
	data["serverkey"] = cfg.ServerKey
	data["agent_version"] = Version
	data["timestamp"] = time.Now().UTC().Format(time.RFC3339)

	// Send data
	if err := dataSender.Send(data); err != nil {
		log.Printf("Send error: %v", err)
	} else {
		log.Printf("Data sent successfully")
	}
}
