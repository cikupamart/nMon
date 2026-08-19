//go:build !windows

package collector

import (
	"net"
	"time"
)

func dialSocket(socketPath string) (net.Conn, error) {
	return net.DialTimeout("unix", socketPath, 5*time.Second)
}
