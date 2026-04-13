<?php
/**
 * Settings Model
 */

declare(strict_types=1);

class Settings extends Model
{
    protected static string $table = 'settings';

    /**
     * Get setting by key
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->queryOne(
            "SELECT value FROM settings WHERE key = ?",
            [$key]
        );

        if (!$setting) return $default;

        $value = json_decode($setting['value'], true);
        return $value ?? $default;
    }

    /**
     * Set setting
     */
    public function set(string $key, $value, ?string $description = null): array
    {
        $existing = $this->queryOne(
            "SELECT id FROM settings WHERE key = ?",
            [$key]
        );

        $jsonValue = json_encode($value);

        if ($existing) {
            $sql = "UPDATE settings SET value = ?, description = ? WHERE key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$jsonValue, $description, $key]);
            return $this->queryOne("SELECT * FROM settings WHERE key = ?", [$key]);
        } else {
            $id = str_replace('-', '', uniqid() . bin2hex(random_bytes(6)));
            $sql = "INSERT INTO settings (id, key, value, description) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $key, $jsonValue, $description]);
            return $this->find($id);
        }
    }

    /**
     * Get all settings
     */
    public function getAll(): array
    {
        $sql = "SELECT key, value FROM settings";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($results as $setting) {
            $settings[$setting['key']] = json_decode($setting['value'], true);
        }

        return $settings;
    }

    /**
     * Delete setting
     */
    public function deleteSetting(string $key): bool
    {
        $sql = "DELETE FROM settings WHERE key = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$key]);
    }
}
