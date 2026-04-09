<?php
class Playlists {
    private $db;
    private $dbInstance;
    private $phpDataPath;
    private $jsonPath;

    public function __construct(){
        $this->dbInstance = Database::getInstance();
        $this->db = $this->dbInstance->getConnection();
        if (!$this->db || !($this->db instanceof PDO)) {
            $this->db = null;
        }
        $this->phpDataPath = __DIR__ . '/../../DATA/playlists.php';
    }

    public function getAll(){
        if (!$this->db) {
            if (file_exists($this->phpDataPath)) {
                $data = include $this->phpDataPath;
                $playlists = is_array($data) ? $data : [];
                // Normalize song paths for file-backed playlists
                foreach ($playlists as &$pl) {
                    if (!empty($pl['songs']) && is_array($pl['songs'])) {
                        $pl['songs'] = $this->normalizeSongs($pl['songs']);
                    }
                }
                return $playlists;
            }
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM playlists ORDER BY created_at DESC");
        $stmt->execute();
        $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($playlists as &$playlist) {
            $songsStmt = $this->db->prepare("SELECT song_title FROM playlist_songs WHERE playlist_id = :playlist_id ORDER BY position");
            $songsStmt->execute([':playlist_id' => $playlist['id']]);
            $songs = $songsStmt->fetchAll(PDO::FETCH_COLUMN);
            $playlist['songs'] = $this->normalizeSongs(is_array($songs) ? $songs : []);
        }
        return $playlists;
    }

    public function getById($id){
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM playlists WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($playlist) {
            $songsStmt = $this->db->prepare("SELECT song_title FROM playlist_songs WHERE playlist_id = :playlist_id ORDER BY position");
            $songsStmt->execute([':playlist_id' => $id]);
            $songs = $songsStmt->fetchAll(PDO::FETCH_COLUMN);
            $playlist['songs'] = $this->normalizeSongs(is_array($songs) ? $songs : []);
        }
        return $playlist;
    }

    /**
     * Normalize an array of song identifiers into playable URLs when possible.
     * - If entry is an absolute URL or starts with '/', keep as-is.
     * - Otherwise try to match a file in public/uploads by basename (case-insensitive).
     */
    private function normalizeSongs(array $songs){
        $out = [];
        $publicRoot = realpath(__DIR__ . '/../../public');
        $audioDirs = [];
        if ($publicRoot && is_dir($publicRoot)) {
            $audioDirs[] = $publicRoot . DIRECTORY_SEPARATOR . 'uploads';
            $audioDirs[] = $publicRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'audios';
        }

        $map = [];
        foreach ($audioDirs as $dir) {
            if (!is_dir($dir)) continue;
            $files = scandir($dir);
            if (!$files) continue;
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $dir . DIRECTORY_SEPARATOR . $f;
                if (!is_file($full)) continue;
                $nameNoExt = strtolower(pathinfo($f, PATHINFO_FILENAME));
                if (strpos($dir, DIRECTORY_SEPARATOR . 'uploads') !== false) {
                    $map[$nameNoExt] = '/uploads/' . $f;
                } else {
                    $map[$nameNoExt] = '/assets/audios/' . $f;
                }
            }
        }

        $publicRootNorm = $publicRoot ? str_replace('\\','/', $publicRoot) : null;
        foreach ($songs as $s) {
            if (!is_string($s) || $s === '') { continue; }
            $sTrim = trim($s);
            if (preg_match('#^https?://#i', $sTrim) || strpos($sTrim, '//') === 0) {
                $out[] = $sTrim;
                continue;
            }
            if (preg_match('#^/public/#', $sTrim)) {
                $out[] = preg_replace('#^/public#', '', $sTrim);
                continue;
            }
            if (preg_match('#^/(assets|uploads)/#', $sTrim)) {
                $out[] = $sTrim;
                continue;
            }

            if (preg_match('/\.(mp3|m4a|ogg|wav|mp4)$/i', $sTrim)) {
                $candidate = __DIR__ . '/../../public/' . ltrim($sTrim, '/');
                $realCandidate = realpath($candidate);
                if ($realCandidate && file_exists($realCandidate) && $publicRootNorm) {
                    $web = str_replace('\\','/', $realCandidate);
                    $web = preg_replace('#^' . preg_quote($publicRootNorm, '#') . '#', '', $web);
                    $out[] = $web ?: '/' . basename($realCandidate);
                    continue;
                }
            }

            $key = strtolower(preg_replace('/\.[^.]+$/', '', $sTrim));
            if (isset($map[$key])) {
                $out[] = $map[$key];
                continue;
            }

            $out[] = $sTrim;
        }
        return $out;
    }

    public function add($playlist){
        if (!$this->db) return false;
        // support optional created_at provided by controller (to assign weekday)
        if (!empty($playlist['created_at'])) {
            $stmt = $this->db->prepare("INSERT INTO playlists (title, description, cover, created_at) 
                                       VALUES (:title, :description, :cover, :created_at)");
            $stmt->execute([
                ':title' => $playlist['title'],
                ':description' => $playlist['desc'] ?? null,
                ':cover' => $playlist['cover'] ?? null,
                ':created_at' => $playlist['created_at']
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO playlists (title, description, cover) 
                                       VALUES (:title, :description, :cover)");
            $stmt->execute([
                ':title' => $playlist['title'],
                ':description' => $playlist['desc'] ?? null,
                ':cover' => $playlist['cover'] ?? null
            ]);
        }
        $playlistId = $this->db->lastInsertId();
        if (!empty($playlist['songs'])) {
            $songStmt = $this->db->prepare("INSERT INTO playlist_songs (playlist_id, song_title, position) 
                                          VALUES (:playlist_id, :song_title, :position)");
            foreach ($playlist['songs'] as $position => $song) {
                $songStmt->execute([
                    ':playlist_id' => $playlistId,
                    ':song_title' => $song,
                    ':position' => $position + 1
                ]);
            }
        }
        return $playlistId;
    }

    public function update($id, $playlist){
        if (!$this->db) return false;
        // optionally update created_at if provided
        if (isset($playlist['created_at'])) {
            $stmt = $this->db->prepare("UPDATE playlists SET title = :title, description = :description, cover = :cover, created_at = :created_at 
                                       WHERE id = :id");
            $success = $stmt->execute([
                ':id' => $id,
                ':title' => $playlist['title'],
                ':description' => $playlist['desc'] ?? null,
                ':cover' => $playlist['cover'] ?? null,
                ':created_at' => $playlist['created_at']
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE playlists SET title = :title, description = :description, cover = :cover 
                                       WHERE id = :id");
            $success = $stmt->execute([
                ':id' => $id,
                ':title' => $playlist['title'],
                ':description' => $playlist['desc'] ?? null,
                ':cover' => $playlist['cover'] ?? null
            ]);
        }
        if ($success) {
            $delStmt = $this->db->prepare("DELETE FROM playlist_songs WHERE playlist_id = :playlist_id");
            $delStmt->execute([':playlist_id' => $id]);
            if (!empty($playlist['songs'])) {
                $songStmt = $this->db->prepare("INSERT INTO playlist_songs (playlist_id, song_title, position) 
                                              VALUES (:playlist_id, :song_title, :position)");
                foreach ($playlist['songs'] as $position => $song) {
                    $songStmt->execute([
                        ':playlist_id' => $id,
                        ':song_title' => $song,
                        ':position' => $position + 1
                    ]);
                }
            }
        }
        return $success;
    }

    public function delete($id){
        if (!$this->db) return false;
        $delSongsStmt = $this->db->prepare("DELETE FROM playlist_songs WHERE playlist_id = :playlist_id");
        $delSongsStmt->execute([':playlist_id' => $id]);
        $stmt = $this->db->prepare("DELETE FROM playlists WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

