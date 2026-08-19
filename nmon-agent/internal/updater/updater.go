package updater

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"time"
)

type Updater struct {
	currentVersion string
	updateURL      string
	checkInterval  time.Duration
	client         *http.Client
}

type UpdateInfo struct {
	Version     string `json:"version"`
	DownloadURL string `json:"download_url"`
	Checksum    string `json:"checksum"`
	ReleaseNote string `json:"release_note"`
	MinVersion  string `json:"min_version"`
}

type VersionResponse struct {
	Latest  UpdateInfo   `json:"latest"`
	All     []UpdateInfo `json:"all"`
}

func NewUpdater(currentVersion, updateURL string) *Updater {
	return &Updater{
		currentVersion: currentVersion,
		updateURL:      updateURL,
		checkInterval:  1 * time.Hour,
		client: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

func (u *Updater) StartAutoUpdate(done <-chan struct{}) {
	ticker := time.NewTicker(u.checkInterval)
	defer ticker.Stop()

	// Initial check after 5 minutes
	go func() {
		time.Sleep(5 * time.Minute)
		u.checkAndUpdate()
	}()

	for {
		select {
		case <-ticker.C:
			u.checkAndUpdate()
		case <-done:
			return
		}
	}
}

func (u *Updater) checkAndUpdate() {
	fmt.Println("🔍 Checking for updates...")

	info, err := u.checkForUpdate()
	if err != nil {
		fmt.Printf("⚠️ Update check failed: %v\n", err)
		return
	}

	if info == nil {
		fmt.Println("✅ Agent is up to date")
		return
	}

	fmt.Printf("📦 New version available: %s (current: %s)\n", info.Version, u.currentVersion)
	fmt.Printf("📝 Release notes: %s\n", info.ReleaseNote)

	if err := u.performUpdate(info); err != nil {
		fmt.Printf("❌ Update failed: %v\n", err)
		return
	}

	fmt.Println("✅ Update completed successfully!")
	fmt.Println("🔄 Agent will restart in 5 seconds...")
	time.Sleep(5 * time.Second)

	// Restart the agent
	u.restart()
}

func (u *Updater) checkForUpdate() (*UpdateInfo, error) {
	resp, err := u.client.Get(u.updateURL + "/version.json")
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return nil, nil // No update available
	}

	var versionResp VersionResponse
	if err := json.NewDecoder(resp.Body).Decode(&versionResp); err != nil {
		return nil, err
	}

	// Compare versions
	if u.compareVersions(versionResp.Latest.Version, u.currentVersion) <= 0 {
		return nil, nil // Already up to date
	}

	return &versionResp.Latest, nil
}

func (u *Updater) performUpdate(info *UpdateInfo) error {
	// Create backup
	backupPath, err := u.createBackup()
	if err != nil {
		return fmt.Errorf("failed to create backup: %w", err)
	}
	fmt.Printf("📦 Backup created: %s\n", backupPath)

	// Download new version
	downloadPath, err := u.download(info)
	if err != nil {
		u.restoreBackup(backupPath)
		return fmt.Errorf("failed to download update: %w", err)
	}

	// Verify checksum
	if err := u.verifyChecksum(downloadPath, info.Checksum); err != nil {
		os.Remove(downloadPath)
		u.restoreBackup(backupPath)
		return fmt.Errorf("checksum verification failed: %w", err)
	}

	// Install update
	if err := u.install(downloadPath); err != nil {
		u.restoreBackup(backupPath)
		return fmt.Errorf("failed to install update: %w", err)
	}

	// Clean up
	os.Remove(downloadPath)

	return nil
}

func (u *Updater) createBackup() (string, error) {
	currentPath, err := os.Executable()
	if err != nil {
		return "", err
	}

	backupDir := filepath.Join(filepath.Dir(currentPath), "backups")
	if err := os.MkdirAll(backupDir, 0755); err != nil {
		return "", err
	}

	backupPath := filepath.Join(backupDir, 
		fmt.Sprintf("nmon-agent-%s-%s", u.currentVersion, time.Now().Format("20060102-150405")))

	if runtime.GOOS == "windows" {
		backupPath += ".exe"
	}

	// Copy current executable to backup
	src, err := os.Open(currentPath)
	if err != nil {
		return "", err
	}
	defer src.Close()

	dst, err := os.Create(backupPath)
	if err != nil {
		return "", err
	}
	defer dst.Close()

	if _, err := io.Copy(dst, src); err != nil {
		return "", err
	}

	// Preserve permissions
	info, err := os.Stat(currentPath)
	if err == nil {
		os.Chmod(backupPath, info.Mode())
	}

	return backupPath, nil
}

func (u *Updater) download(info *UpdateInfo) (string, error) {
	// Determine download URL based on OS
	ext := ""
	if runtime.GOOS == "windows" {
		ext = ".exe"
	}

	downloadURL := fmt.Sprintf("%s/%s/nmon-agent-%s%s", 
		info.DownloadURL, runtime.GOOS, runtime.GOARCH, ext)

	fmt.Printf("📥 Downloading from: %s\n", downloadURL)

	resp, err := u.client.Get(downloadURL)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("download failed with status %d", resp.StatusCode)
	}

	// Create temporary file
	tmpFile, err := os.CreateTemp("", "nmon-agent-update-*")
	if err != nil {
		return "", err
	}
	defer tmpFile.Close()

	// Write to file
	if _, err := io.Copy(tmpFile, resp.Body); err != nil {
		os.Remove(tmpFile.Name())
		return "", err
	}

	return tmpFile.Name(), nil
}

func (u *Updater) verifyChecksum(filePath, expectedChecksum string) error {
	file, err := os.Open(filePath)
	if err != nil {
		return err
	}
	defer file.Close()

	hash := sha256.New()
	if _, err := io.Copy(hash, file); err != nil {
		return err
	}

	actualChecksum := hex.EncodeToString(hash.Sum(nil))
	if actualChecksum != expectedChecksum {
		return fmt.Errorf("expected %s, got %s", expectedChecksum, actualChecksum)
	}

	return nil
}

func (u *Updater) install(downloadPath string) error {
	currentPath, err := os.Executable()
	if err != nil {
		return err
	}

	// Make downloaded file executable
	if err := os.Chmod(downloadPath, 0755); err != nil {
		return err
	}

	// On Windows, we can't replace a running executable directly
	if runtime.GOOS == "windows" {
		return u.installWindows(downloadPath, currentPath)
	}

	// On Linux/Mac, use atomic replacement
	return u.installUnix(downloadPath, currentPath)
}

func (u *Updater) installUnix(downloadPath, currentPath string) error {
	// Create symlink replacement
	tmpPath := currentPath + ".new"
	
	// Remove any existing .new file
	os.Remove(tmpPath)

	// Move new binary to .new
	if err := os.Rename(downloadPath, tmpPath); err != nil {
		return err
	}

	// Atomic rename (on most filesystems)
	if err := os.Rename(tmpPath, currentPath); err != nil {
		// Fallback: copy and replace
		return u.copyFile(tmpPath, currentPath)
	}

	return nil
}

func (u *Updater) installWindows(downloadPath, currentPath string) error {
	// On Windows, move current to .old and new to current
	oldPath := currentPath + ".old"
	newPath := currentPath + ".new"

	// Remove old backup
	os.Remove(oldPath)

	// Rename current to .old
	if err := os.Rename(currentPath, oldPath); err != nil {
		return err
	}

	// Move new to current
	if err := os.Rename(downloadPath, newPath); err != nil {
		os.Rename(oldPath, currentPath) // Restore
		return err
	}

	if err := os.Rename(newPath, currentPath); err != nil {
		os.Rename(oldPath, currentPath) // Restore
		return err
	}

	return nil
}

func (u *Updater) restoreBackup(backupPath string) error {
	currentPath, err := os.Executable()
	if err != nil {
		return err
	}

	fmt.Printf("⚠️ Restoring backup from: %s\n", backupPath)
	return u.copyFile(backupPath, currentPath)
}

func (u *Updater) copyFile(src, dst string) error {
	srcFile, err := os.Open(src)
	if err != nil {
		return err
	}
	defer srcFile.Close()

	dstFile, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer dstFile.Close()

	if _, err := io.Copy(dstFile, srcFile); err != nil {
		return err
	}

	// Preserve permissions
	info, err := os.Stat(src)
	if err == nil {
		os.Chmod(dst, info.Mode())
	}

	return nil
}

func (u *Updater) restart() {
	currentPath, err := os.Executable()
	if err != nil {
		fmt.Printf("❌ Failed to get executable path: %v\n", err)
		return
	}

	// Start new process
	cmd := exec.Command(currentPath, os.Args[1:]...)
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	cmd.Stdin = os.Stdin

	if err := cmd.Start(); err != nil {
		fmt.Printf("❌ Failed to restart: %v\n", err)
		return
	}

	// Exit current process
	fmt.Println("👋 Goodbye!")
	os.Exit(0)
}

func (u *Updater) compareVersions(v1, v2 string) int {
	// Simple version comparison (assumes semver)
	// Returns: 1 if v1 > v2, -1 if v1 < v2, 0 if equal
	
	type version struct {
		major, minor, patch int
	}

	parseVersion := func(v string) version {
		var ver version
		fmt.Sscanf(v, "%d.%d.%d", &ver.major, &ver.minor, &ver.patch)
		return ver
	}

	ver1 := parseVersion(v1)
	ver2 := parseVersion(v2)

	if ver1.major != ver2.major {
		if ver1.major > ver2.major {
			return 1
		}
		return -1
	}

	if ver1.minor != ver2.minor {
		if ver1.minor > ver2.minor {
			return 1
		}
		return -1
	}

	if ver1.patch != ver2.patch {
		if ver1.patch > ver2.patch {
			return 1
		}
		return -1
	}

	return 0
}

// SetInterval allows customizing the check interval
func (u *Updater) SetInterval(interval time.Duration) {
	u.checkInterval = interval
}

// ForceUpdate forces an update check and installation
func (u *Updater) ForceUpdate() error {
	info, err := u.checkForUpdate()
	if err != nil {
		return err
	}

	if info == nil {
		return fmt.Errorf("no update available")
	}

	return u.performUpdate(info)
}

// Rollback restores the previous version
func (u *Updater) Rollback() error {
	currentPath, err := os.Executable()
	if err != nil {
		return err
	}

	backupDir := filepath.Join(filepath.Dir(currentPath), "backups")
	
	// Find most recent backup
	entries, err := os.ReadDir(backupDir)
	if err != nil {
		return fmt.Errorf("no backups found: %w", err)
	}

	if len(entries) == 0 {
		return fmt.Errorf("no backups available")
	}

	// Get most recent backup
	latestBackup := entries[len(entries)-1]
	backupPath := filepath.Join(backupDir, latestBackup.Name())

	fmt.Printf("⏪ Rolling back to: %s\n", latestBackup.Name())
	return u.copyFile(backupPath, currentPath)
}
