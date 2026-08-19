//go:build windows

package collector

import (
	"net"
	"time"
)

func dialSocket(pipePath string) (net.Conn, error) {
	return net.DialTimeout("npipe", `\\.\pipe\docker_engine`, 5*time.Second)
}
