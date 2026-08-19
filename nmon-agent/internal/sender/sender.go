package sender

import (
	"bytes"
	"compress/gzip"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"
)

type Sender interface {
	Send(data map[string]interface{}) error
}

type HTTPSender struct {
	gateway   string
	serverKey string
	client    *http.Client
}

func NewHTTPSender(gateway, serverKey string) *HTTPSender {
	return &HTTPSender{
		gateway:   gateway,
		serverKey: serverKey,
		client: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

func (s *HTTPSender) Send(data map[string]interface{}) error {
	// Marshal data to JSON
	jsonData, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("failed to marshal data: %w", err)
	}

	// Compress data
	var compressedData bytes.Buffer
	gzWriter, _ := gzip.NewWriterLevel(&compressedData, gzip.BestCompression)
	if _, err := gzWriter.Write(jsonData); err != nil {
		return fmt.Errorf("failed to compress data: %w", err)
	}
	if err := gzWriter.Close(); err != nil {
		return fmt.Errorf("failed to close gzip writer: %w", err)
	}

	// Create request
	req, err := http.NewRequest("POST", s.gateway, &compressedData)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	// Set headers
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Content-Encoding", "gzip")
	req.Header.Set("X-Server-Key", s.serverKey)
	req.Header.Set("User-Agent", "nMon-Agent/2.0")

	// Send request
	resp, err := s.client.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send data: %w", err)
	}
	defer resp.Body.Close()

	// Check response
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("server returned error %d: %s", resp.StatusCode, string(body))
	}

	return nil
}
