#!/bin/bash

# nMon Agent v2.0 - Linux
# Features: Monitoring + Remote Control

# Set environment
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

GATEWAY=$(cat /opt/nmon/gateway)
SERVERKEY=$(cat /opt/nmon/serverkey)
AGENT_DIR="/opt/nmon"
REMOTE_DIR="$AGENT_DIR/remote"
LOG_FILE="$AGENT_DIR/agent.log"

# Create directories
mkdir -p "$REMOTE_DIR" 2>/dev/null

#============\n# Functions\n#============

function log() {\n\techo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"\n}

function encode() {\n\techo "$1" | base64\n}

function getOS() {\n\tif [ -f /etc/lsb-release ]; then\n\t\tos_name=$(lsb_release -s -d)\n\telif [ -f /etc/debian_version ]; then\n\t\tos_name="Debian $(cat /etc/debian_version)"\n\telif [ -f /etc/redhat-release ]; then\n\t\tos_name=`cat /etc/redhat-release`\n\telse\n\t\tos_name="$(cat /etc/*release | grep '^PRETTY_NAME=\\|^NAME=\\|^DISTRIB_ID=' | awk -F\\= '{print $2}' | tr -d '\"' | tac)"\n\t\tif [ -z "$os_name" ]; then\n\t\t\tos_name="$(uname -s)"\n\t\tfi\n\tfi\n\techo "$os_name"\n}

function cpuSpeed() {\n\tcpu_speed=$(cat /proc/cpuinfo | grep 'cpu MHz' | awk -F\\: '{print $2}' | uniq)\n\tif [ -z "$cpu_speed" ]; then\n\t\tcpu_speed=$(lscpu | grep 'CPU MHz' | awk -F\\: '{print $2}' | sed -e 's/^ *//g' -e 's/ *$//g')\n\tfi\n\techo "$cpu_speed"\n}

function defaultInterface() {\n\tinterface="$(ip route get 4.2.2.1 | grep dev | awk -F'dev' '{print $2}' | awk '{print $1}')"\n\tif [ -z $interface ]; then\n\t\tinterface="$(ip link show | grep 'eth[0-9]' | awk '{print $2}' | tr -d ':' | head -n1)"\n\tfi\n\techo "$interface"\n}

function activeConnections() {\n\tif [ -n "$(command -v ss)" ]; then\n\t\tactive_connections="$(ss -tun | tail -n +2 | wc -l)"\n\telse\n\t\tactive_connections="$(netstat -tun | tail -n +3 | wc -l)"\n\tfi\n\techo "$active_connections"\n}

function pingLatency() {\n\tping_google="$(ping -B -w 2 -n -c 2 google.com | grep rtt | awk -F '/' '{print $5}')"\n\techo "$ping_google"\n}

#============\n# Remote Command Handler\n#============

function checkRemoteCommands() {\n\tlocal commands_file="$REMOTE_DIR/commands.json"\n\t\n\tif [ ! -f "$commands_file" ]; then\n\t\treturn\n\tfi\n\t\n\t# Read and process commands\n\twhile IFS= read -r line; do\n\t\tif [ -n "$line" ]; then\n\t\t\tlocal cmd_id=$(echo "$line" | grep -o '"id":[0-9]*' | cut -d':' -f2)\n\t\t\tlocal command=$(echo "$line" | grep -o '"command":"[^"]*"' | cut -d'"' -f4)\n\t\t\t\n\t\t\tif [ -n "$cmd_id" ] && [ -n "$command" ]; then\n\t\t\t\texecuteRemoteCommand "$cmd_id" "$command"\n\t\t\tdone\n\t\tdone < "$commands_file"\n\t\t\n\t\t# Clear processed commands\n\t\trm -f "$commands_file"\n\tfi\n}

function executeRemoteCommand() {\n\tlocal cmd_id=$1\n\tlocal command=$2\n\t\n\tlog "Executing remote command ID: $cmd_id - $command"\n\t\n\t# Security: Check if command is allowed\n\tif ! isCommandAllowed "$command"; then\n\t\tsendCommandResult "$cmd_id" "1" "Command not allowed: $command"\n\t\treturn\n\tfi\n\t\n\t# Execute command and capture output\n\tlocal output\n\tlocal exit_code\n\t\n\toutput=$(eval "$command" 2>&1)\n\texit_code=$?\n\t\n\tsendCommandResult "$cmd_id" "$exit_code" "$output"\n}

function isCommandAllowed() {\n\tlocal command=$1\n\t\n\t# List of allowed commands\n\tlocal allowed_commands=(\n\t\t"ls" "df" "du" "free" "uptime" "top" "ps" "netstat" "ss"\n\t\t"cat /proc/cpuinfo" "cat /proc/meminfo" "cat /proc/loadavg"\n\t\t"uname -a" "hostname" "date" "who" "w" "last"\n\t\t"systemctl status" "service status"\n\t\t"ping -c" "traceroute" "dig" "nslookup"\n\t\t"tail -n" "head -n" "grep" "find" "wc"\n\t\t"iptables -L" "ufw status"\n\t\t"docker ps" "docker images"\n\t\t"journalctl -n"\n\t)\n\t\n\t# Check if command starts with any allowed command\n\tfor allowed in "${allowed_commands[@]}"; do\n\t\tif [[ "$command" == $allowed* ]]; then\n\t\t\treturn 0\n\t\tdone\n\tdone\n\t\n\treturn 1\n}

function sendCommandResult() {\n\tlocal cmd_id=$1\n\tlocal exit_code=$2\n\tlocal output=$3\n\t\n\t# Encode output\n\tlocal encoded_output=$(echo "$output" | base64)\n\t\n\t# Send result to server\n\tcurl -s -X POST "$GATEWAY" \\\n\t\t-H "Content-Type: application/x-www-form-urlencoded" \\\n\t\t-d "action=command_result" \\\n\t\t-d "cmd_id=$cmd_id" \\\n\t\t-d "exit_code=$exit_code" \\\n\t\t-d "output=$encoded_output" \\\n\t\t-d "serverkey=$SERVERKEY"\n}

#============\n# Main Agent\n#============

log "Agent started"

# Check for remote commands\ncheckRemoteCommands

# agent version\nagent_version=\"2.0\"\nPOST=\"$POST{agent_version}$agent_version{/agent_version}\"\n\n# serverkey\nPOST=\"$POST{serverkey}$serverkey{/serverkey}\"\n\n# gateway\nPOST=\"$POST{gateway}$gateway{/gateway}\"\n\n# hostname\nhostname=$(hostname)\nPOST=\"$POST{hostname}$hostname{/hostname}\"\n\n# kernel\nkernel=$(uname -r)\nPOST=\"$POST{kernel}$kernel{/kernel}\"\n\n# time\ntime=$(date +%s)\nPOST=\"$POST{time}$time{/time}\"\n\n# OS\nos=$(getOS)\nPOST=\"$POST{os}$os{/os}\"\n\n# OS Arch\nos_arch=`uname -m`\",\"`uname -p`\nPOST=\"$POST{os_arch}$os_arch{/os_arch}\"\n\n# CPU Model\ncpu_model=$(cat /proc/cpuinfo | grep 'model name' | awk -F\\: '{print $2}' | uniq)\nPOST=\"$POST{cpu_model}$cpu_model{/cpu_model}\"\n\n# CPU Cores\ncpu_cores=$(cat /proc/cpuinfo | grep processor | wc -l)\nPOST=\"$POST{cpu_cores}$cpu_cores{/cpu_cores}\"\n\n# CPU Speed\ncpu_speed=$(cpuSpeed)\nPOST=\"$POST{cpu_speed}$cpu_speed{/cpu_speed}\"\n\n# CPU Load\ncpu_load=$(cat /proc/loadavg | awk '{print $1\",\"$2\",\"$3}')\nPOST=\"$POST{cpu_load}$cpu_load{/cpu_load}\"\n\n##### CPU Info\ncpu_info=$(grep -i cpu /proc/stat | awk '{print $1\",\"$2\",\"$3\",\"$4\",\"$5\",\"$6\",\"$7\",\"$8\",\"$9\",\"$10\",\"$11\";\"}' | tr -d '\\n')\nPOST=\"$POST{cpu_info}$cpu_info{/cpu_info}\"\nsleep 1s\ncpu_info_current=$(grep -i cpu /proc/stat | awk '{print $1\",\"$2\",\"$3\",\"$4\",\"$5\",\"$6\",\"$7\",\"$8\",\"$9\",\"$10\",\"$11\";\"}' | tr -d '\\n')\nPOST=\"$POST{cpu_info_current}$cpu_info_current{/cpu_info_current}\"\n\n# Disks\ndisks=$(df -P -T -B 1k | grep '^/' | awk '{print $1\",\"$2\",\"$3\",\"$4\",\"$5\",\"$6\",\"$7\";\"}' | tr -d '\\n')\nPOST=\"$POST{disks}$disks{/disks}\"\n\n# Disk usage\ndisks_inodes=$(df -P -i | grep '^/' | awk '{print $1\",\"$2\",\"$3\",\"$4\",\"$5\",\"$6\";\"}' | tr -d '\\n')\nPOST=\"$POST{disks_inodes}$disanks_inodes{/disks_inodes}\"\n\n# File descriptors\nfile_descriptors=$(cat /proc/sys/fs/file-nr | awk '{print $1\",\"$2\",\"$3}')\nPOST=\"$POST{file_descriptors}$file_descriptors{/file_descriptors}\"\n\n# RAM Total\nram_total=$(free | grep ^Mem: | awk '{print $2}')\nPOST=\"$POST{ram_total}$ram_total{/ram_total}\"\n\n# RAM Free\nram_free=$(free | grep ^Mem: | awk '{print $4}')\nPOST=\"$POST{ram_free}$ram_free{/ram_free}\"\n\n# RAM Caches\nram_caches=$(free | grep ^Mem: | awk '{print $6}')\nPOST=\"$POST{ram_caches}$ram_caches{/ram_caches}\"\n\n# RAM Buffers\nram_buffers=0\nPOST=\"$POST{ram_buffers}$ram_buffers{/ram_buffers}\"\n\n# RAM USAGE\nram_usage=$(free | grep ^Mem: | awk '{print $3}')\nPOST=\"$POST{ram_usage}$ram_usage{/ram_usage}\"\n\n# SWAP Total\nswap_total=$(cat /proc/meminfo | grep ^SwapTotal: | awk '{print $2}')\nPOST=\"$POST{swap_total}$swap_total{/swap_total}\"\n\n# SWAP Free\nswap_free=$(cat /proc/meminfo | grep ^SwapFree: | awk '{print $2}')\nPOST=\"$POST{swap_free}$swap_free{/swap_free}\"\n\n# SWAP Usage\nswap_usage=$(($swap_total-$swap_free))\nPOST=\"$POST{swap_usage}$swap_usage{/swap_usage}\"\n\n# Default Interface\ndefault_interface=$(defaultInterface)\nPOST=\"$POST{default_interface}$default_interface{/default_interface}\"\n\n# All Interfaces\nall_interfaces=$(tail -n +3 /proc/net/dev | tr \":\" \" \" | awk '{print $1\",\"$2\",\"$10\",\"$3\",\"$11\";\"}' | tr -d ':' | tr -d '\\n')\nPOST=\"$POST{all_interfaces}$all_interfaces{/all_interfaces}\"\nsleep 1s\nall_interfaces_current=$(tail -n +3 /proc/net/dev | tr \":\" \" \" | awk '{print $1\",\"$2\",\"$10\",\"$3\",\"$11\";\"}' | tr -d ':' | tr -d '\\n')\nPOST=\"$POST{all_interfaces_current}$all_interfaces_current{/all_interfaces_current}\"\n\n# IPv4 Addresses\nipv4_addresses=$(ip -f inet -o addr show | awk '{split($4,a,\"/\"); print $2\",\"a[1]\";\"}' | tr -d '\\n')\nPOST=\"$POST{ipv4_addresses}$ipv4_addresses{/ipv4_addresses}\"\n\n# IPv6 Addresses\nipv6_addresses=$(ip -f inet6 -o addr show | awk '{split($4,a,\"/\"); print $2\",\"a[1]\";\"}' | tr -d '\\n')\nPOST=\"$POST{ipv6_addresses}$ipv6_addresses{/ipv6_addresses}\"\n\n# Active Connections\nactive_connections=$(activeConnections)\nPOST=\"$POST{active_connections}$active_connections{/active_connections}\"\n\n# Ping Latency\nping_latency=$(pingLatency)\nPOST=\"$POST{ping_latency}$ping_latency{/ping_latency}\"\n\n# SSH Sessions\nssh_sessions=$(who | wc -l)\nPOST=\"$POST{ssh_sessions}$ssh_sessions{/ssh_sessions}\"\n\n# Uptime\nuptime=$(cat /proc/uptime | awk '{print $1}')\nPOST=\"$POST{uptime}$uptime{/uptime}\"\n\n# Processes\nprocesses=$(ps -e -o pid,ppid,rss,vsz,uname,pmem,pcpu,comm,cmd --sort=-pcpu,-pmem | awk '{print $1\",\"$2\",\"$3\",\"$4\",\"$5\",\"$6\",\"$7\",\"$8\",\"$9\";\"}' | tr -d '\\n')\nPOST=\"$POST{processes}$processes{/processes}\"\n\n# Location (for live maps)\nif [ -f "$AGENT_DIR/lat" ] && [ -f "$AGENT_DIR/lng" ]; then\n\tlat=$(cat "$AGENT_DIR/lat")\n\tlng=$(cat "$AGENT_DIR/lng")\n\tPOST=\"$POST{lat}$lat{/lat}\"\n\tPOST=\"$POST{lng}$lng{/lng}\"\nfi\n\n# Upload data\n# -m max-time in seconds\n# -k insecure\n# -s silent\n# -d data\n\necho \"data=$POST\" | curl -m 50 -k -s -d @- \"$GATEWAY\"\n\nlog \"Agent completed\"\n