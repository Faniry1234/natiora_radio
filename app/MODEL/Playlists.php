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
                return is_array($data) ? $data : [];
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
            $playlist['songs'] = $songs;
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
            $playlist['songs'] = $songs;
        }
        return $playlist;
    }

    public function add($playlist){
        if (!$this->db) return false;
        $stmt = $this->db->prepare("INSERT INTO playlists (title, description, cover) 
                                   VALUES (:title, :description, :cover)");
        $stmt->execute([
            ':title' => $playlist['title'],
            ':description' => $playlist['desc'] ?? null,
            ':cover' => $playlist['cover'] ?? null
        ]);
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
        $stmt = $this->db->prepare("UPDATE playlists SET title = :title, description = :description, cover = :cover 
                                   WHERE id = :id");
        $success = $stmt->execute([
            ':id' => $id,
            ':title' => $playlist['title'],
            ':description' => $playlist['desc'] ?? null,
            ':cover' => $playlist['cover'] ?? null
        ]);
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

