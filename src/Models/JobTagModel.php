<?php

namespace Drivejob\Models;

class JobTagModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($name) {
        $sql = "INSERT INTO job_tags (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name) {
        $sql = "UPDATE job_tags SET name = :name WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'name' => $name
        ]);
    }

    public function getById($id) {
        $sql = "SELECT * FROM job_tags WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $sql = "DELETE FROM job_tags WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getAllTags() {
        $sql = "SELECT * FROM job_tags ORDER BY name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByName($name) {
        $sql = "SELECT * FROM job_tags WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteAllTagsForJob($jobListingId) {
        $sql = "DELETE FROM job_listing_tags WHERE job_listing_id = :job_listing_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['job_listing_id' => $jobListingId]);
    }

    public function getPopularTags($limit = 10) {
        $sql = "SELECT jt.*, COUNT(jlt.job_listing_id) as usage_count 
                FROM job_tags jt 
                JOIN job_listing_tags jlt ON jt.id = jlt.job_tag_id 
                GROUP BY jt.id 
                ORDER BY usage_count DESC 
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}