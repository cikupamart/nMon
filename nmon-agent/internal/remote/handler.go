package remote

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/exec"
	"runtime"
	"strings"
	"sync"
	"time"

	"github.com/gorilla/websocket"
	"nmon-agent/internal/collector"
	"nmon-agent/internal/config"
)

type Handler struct {
	cfg        *config.Config
	collector  collector.Collector
	server     *http.Server
	wsConn     *websocket.Conn
	wsMutex    sync.Mutex
	ctx        context.Context
	cancel     context.CancelFunc
	cmdHistory []CommandResult
}

type CommandRequest struct {
	ID      string `json:"id"`
	Command string `json:"command"`
	Args    []string `json:"args"`
	Timeout int    `json:"timeout"`
}

type CommandResult struct {
	ID       string `json:"id"`
	Command  string `json:"command"`
	Output   string `json:"output"`
	Error    string `json:"error,omitempty"`
	ExitCode int    `json:"exit_code"`
	Duration int64  `json:"duration_ms"`
}

type AgentStatus struct {
	Status    string `json:"status"`
	Uptime    int64  `json:"uptime"`
	Version   string `json:"version"`
	OS        string `json:"os"`
	Hostname  string `json:"hostname"`
	LastCheck time.Time `json:"last_check"`
}

func NewHandler(cfg *config.Config, collector collector.Collector) *Handler {
	ctx, cancel := context.WithCancel(context.Background())
	return &Handler{
		cfg:       cfg,
		collector: collector,
		ctx:       ctx,
		cancel:    cancel,
	}
}

func (h *Handler) Start() error {
	mux := http.NewServeMux()
	mux.HandleFunc("/ws", h.handleWebSocket)
	mux.HandleFunc("/status", h.handleStatus)
	mux.HandleFunc("/health", h.handleHealth)
	mux.HandleFunc("/execute", h.handleExecuteHTTP)

	addr := fmt.Sprintf("0.0.0.0:%d", h.cfg.RemotePort)
	h.server = &http.Server{
		Addr:    addr,
		Handler: mux,
	}

	log.Printf("Remote control server starting on %s", addr)
	return h.server.ListenAndServe()
}

func (h *Handler) Stop() {
	h.cancel()
	if h.wsConn != nil {
		h.wsConn.Close()
	}
	if h.server != nil {
		shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
		defer cancel()
		h.server.Shutdown(shutdownCtx)
	}
}

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		return true // Allow all origins in development
	},
}

func (h *Handler) handleWebSocket(w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Printf("WebSocket upgrade error: %v", err)
		return
	}

	h.wsMutex.Lock()
	h.wsConn = conn
	h.wsMutex.Unlock()

	log.Printf("New WebSocket connection from %s", r.RemoteAddr)

	// Send initial status
	status := h.getStatus()
	h.sendMessage(map[string]interface{}{
		"type":   "status",
		"status": status,
	})

	// Handle incoming messages
	for {
		_, message, err := conn.ReadMessage()
		if err != nil {
			log.Printf("WebSocket read error: %v", err)
			break
		}

		h.handleMessage(message)
	}

	h.wsMutex.Lock()
	if h.wsConn == conn {
		h.wsConn = nil
	}
	h.wsMutex.Unlock()
}

func (h *Handler) handleMessage(data []byte) {
	var msg struct {
		Type    string          `json:"type"`
		Payload json.RawMessage `json:"payload"`
	}

	if err := json.Unmarshal(data, &msg); err != nil {
		log.Printf("Invalid message format: %v", err)
		return
	}

	switch msg.Type {
	case "execute":
		var cmdReq CommandRequest
		if err := json.Unmarshal(msg.Payload, &cmdReq); err != nil {
			h.sendError("Invalid command request")
			return
		}
		go h.executeCommand(cmdReq)

	case "collect":
		go h.collectAndSend()

	case "ping":
		h.sendMessage(map[string]interface{}{
			"type": "pong",
		})

	default:
		log.Printf("Unknown message type: %s", msg.Type)
	}
}

func (h *Handler) executeCommand(cmdReq CommandRequest) {
	start := time.Now()

	// Security check - validate command
	if !h.isAllowedCommand(cmdReq.Command) {
		result := CommandResult{
			ID:       cmdReq.ID,
			Command:  cmdReq.Command,
			Error:    "Command not allowed",
			ExitCode: -1,
			Duration: time.Since(start).Milliseconds(),
		}
		h.sendCommandResult(result)
		return
	}

	// Create context with timeout
	timeout := time.Duration(cmdReq.Timeout) * time.Second
	if timeout == 0 {
		timeout = 30 * time.Second
	}
	ctx, cancel := context.WithTimeout(h.ctx, timeout)
	defer cancel()

	// Execute command
	var cmd *exec.Cmd
	if runtime.GOOS == "windows" {
		cmd = exec.CommandContext(ctx, "cmd", "/C", cmdReq.Command)
	} else {
		cmd = exec.CommandContext(ctx, cmdReq.Command, cmdReq.Args...)
	}

	output, err := cmd.CombinedOutput()
	exitCode := 0
	if err != nil {
		if exitErr, ok := err.(*exec.ExitError); ok {
			exitCode = exitErr.ExitCode()
		} else {
			exitCode = -1
		}
	}

	result := CommandResult{
		ID:       cmdReq.ID,
		Command:  cmdReq.Command,
		Output:   string(output),
		Error:    err.Error(),
		ExitCode: exitCode,
		Duration: time.Since(start).Milliseconds(),
	}

	// Store in history
	h.cmdHistory = append(h.cmdHistory, result)
	if len(h.cmdHistory) > 100 {
		h.cmdHistory = h.cmdHistory[1:]
	}

	h.sendCommandResult(result)
}

func (h *Handler) isAllowedCommand(command string) bool {
	// Define allowed commands for security
	allowedCommands := []string{
		"ls", "dir", "pwd", "cd",
		"cat", "type", "head", "tail",
		"ps", "tasklist", "top", "htop",
		"df", "du", "free", "wmic",
		"ping", "traceroute", "tracert",
		"netstat", "ss", "ipconfig",
		"uname", "systeminfo",
		"uptime", "w",
		"grep", "find", "where",
		"curl", "wget",
		"systemctl", "service",
	}

	cmdBase := strings.Fields(command)[0]
	for _, allowed := range allowedCommands {
		if cmdBase == allowed {
			return true
		}
	}

	// Block dangerous commands
	blockedCommands := []string{
		"rm", "del", "rmdir", "rd",
		"mkfs", "fdisk", "format",
		"shutdown", "reboot", "halt",
		"passwd", "useradd", "userdel",
		"chmod", "chown",
		"iptables", "netsh",
	}

	for _, blocked := range blockedCommands {
		if cmdBase == blocked {
			return false
		}
	}

	return false
}

func (h *Handler) collectAndSend() {
	data, err := h.collector.Collect()
	if err != nil {
		log.Printf("Collection error: %v", err)
		return
	}

	h.sendMessage(map[string]interface{}{
		"type": "metrics",
		"data": data,
	})
}

func (h *Handler) sendCommandResult(result CommandResult) {
	h.sendMessage(map[string]interface{}{
		"type":    "command_result",
		"result": result,
	})
}

func (h *Handler) sendMessage(msg interface{}) {
	h.wsMutex.Lock()
	defer h.wsMutex.Unlock()

	if h.wsConn == nil {
		return
	}

	data, err := json.Marshal(msg)
	if err != nil {
		log.Printf("Marshal error: %v", err)
		return
	}

	h.wsConn.WriteMessage(websocket.TextMessage, data)
}

func (h *Handler) sendError(message string) {
	h.sendMessage(map[string]interface{}{
		"type":    "error",
		"message": message,
	})
}

func (h *Handler) handleStatus(w http.ResponseWriter, r *http.Request) {
	status := h.getStatus()
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(status)
}

func (h *Handler) handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{
		"status": "healthy",
		"time":   time.Now().UTC().Format(time.RFC3339),
	})
}

func (h *Handler) handleExecuteHTTP(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var cmdReq CommandRequest
	if err := json.NewDecoder(r.Body).Decode(&cmdReq); err != nil {
		http.Error(w, "Invalid request", http.StatusBadRequest)
		return
	}

	// Execute synchronously for HTTP
	start := time.Now()
	output, err := exec.Command(cmdReq.Command, cmdReq.Args...).CombinedOutput()
	exitCode := 0
	if err != nil {
		if exitErr, ok := err.(*exec.ExitError); ok {
			exitCode = exitErr.ExitCode()
		}
	}

	result := CommandResult{
		ID:       cmdReq.ID,
		Command:  cmdReq.Command,
		Output:   string(output),
		Error:    err.Error(),
		ExitCode: exitCode,
		Duration: time.Since(start).Milliseconds(),
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(result)
}

func (h *Handler) getStatus() AgentStatus {
	hostname, _ := os.Hostname()
	return AgentStatus{
		Status:    "online",
		Uptime:    h.getUptime(),
		Version:   "2.0.0",
		OS:        runtime.GOOS,
		Hostname:  hostname,
		LastCheck: time.Now(),
	}
}

func (h *Handler) getUptime() int64 {
	// Implementation depends on OS
	return 0
}
