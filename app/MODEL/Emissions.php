<?php

class Emissions {

    private ?PDO $db;
    private $dbInstance;
    private string $phpDataPath;

    public function __construct(){

        $this->dbInstance = Database::getInstance();
        $this->db = $this->dbInstance->getConnection();

        if (!$this->db instanceof PDO) {
            $this->db = null;
        }

        $this->phpDataPath = __DIR__ . '/../../DATA/emissions.php';
    }

    public function getAll(){

        $result = [
            'lundi'=>[],
            'mardi'=>[],
            'mercredi'=>[],
            'jeudi'=>[],
            'vendredi'=>[],
            'samedi'=>[],
            'dimanche'=>[]
        ];

        // fallback PHP file
        if (!$this->db) {

            if (file_exists($this->phpDataPath)) {

                $data = include $this->phpDataPath;

                if (is_array($data)) {

                    foreach ($data as $emission) {

                        $day = $emission['day'] ?? 'autres';

                        if (!isset($result[$day])) {
                            $result[$day] = [];
                        }

                        $result[$day][] = $emission;
                    }
                }
            }

            return $result;
        }

        // requête SQL
        $stmt = $this->db->prepare("
            SELECT * FROM emissions 
            ORDER BY CASE day
                WHEN 'lundi' THEN 1
                WHEN 'mardi' THEN 2
                WHEN 'mercredi' THEN 3
                WHEN 'jeudi' THEN 4
                WHEN 'vendredi' THEN 5
                WHEN 'samedi' THEN 6
                WHEN 'dimanche' THEN 7
                ELSE 100
            END, time
        ");

        $stmt->execute();

        $emissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($emissions as $emission) {

            $day = $emission['day'] ?? 'autres';

            if (!isset($result[$day])) {
                $result[$day] = [];
            }

            $result[$day][] = $emission;
        }

        return $result;
    }

    public function getByDay($day){

        if (!$this->db) {

            if (file_exists($this->phpDataPath)) {

                $data = include $this->phpDataPath;

                if (is_array($data)) {

                    $out = [];

                    foreach ($data as $em) {

                        if (($em['day'] ?? '') === $day) {
                            $out[] = $em;
                        }
                    }

                    return $out;
                }
            }

            return [];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM emissions 
            WHERE day = :day 
            ORDER BY time
        ");

        $stmt->execute([':day'=>$day]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($day, $emission){

        if (!$this->db) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO emissions 
            (day,time,title,presenter,duration,level,category,src,description)
            VALUES
            (:day,:time,:title,:presenter,:duration,:level,:category,:src,:description)
        ");

        return $stmt->execute([

            ':day'=>$day,
            ':time'=>$emission['time'] ?? null,
            ':title'=>$emission['title'] ?? '',
            ':presenter'=>$emission['presenter'] ?? null,
            ':duration'=>$emission['duration'] ?? null,
            ':level'=>$emission['level'] ?? null,
            ':category'=>$emission['category'] ?? null,
            ':src'=>$emission['src'] ?? null,
            ':description'=>$emission['desc'] ?? null

        ]);
    }

    public function update($day,$index,$emission){

        if (!$this->db) {
            return false;
        }

        $emissions = $this->getByDay($day);

        if (!isset($emissions[$index])) {
            return false;
        }

        $id = $emissions[$index]['id'];

        $stmt = $this->db->prepare("
            UPDATE emissions 
            SET 
                time = :time,
                title = :title,
                presenter = :presenter,
                duration = :duration,
                level = :level,
                category = :category,
                src = :src,
                description = :description
            WHERE id = :id
        ");

        return $stmt->execute([

            ':id'=>$id,
            ':time'=>$emission['time'] ?? null,
            ':title'=>$emission['title'] ?? '',
            ':presenter'=>$emission['presenter'] ?? null,
            ':duration'=>$emission['duration'] ?? null,
            ':level'=>$emission['level'] ?? null,
            ':category'=>$emission['category'] ?? null,
            ':src'=>$emission['src'] ?? null,
            ':description'=>$emission['desc'] ?? null

        ]);
    }

    public function delete($day,$index){

        if (!$this->db) {
            return false;
        }

        $emissions = $this->getByDay($day);

        if (!isset($emissions[$index])) {
            return false;
        }

        $id = $emissions[$index]['id'];

        $stmt = $this->db->prepare("
            DELETE FROM emissions 
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'=>$id
        ]);
    }

}