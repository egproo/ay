<?php
class Queue {
    private $db;
    private $max_attempts = 3; // الحد الأقصى لعدد المحاولات

    public function __construct($db) {
        $this->db = $db;
    }

    public function addJob($job) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "queue_jobs` SET `job` = '" . $this->db->escape(json_encode($job)) . "', `status` = 'pending', `attempts` = 0");
    }

    public function getPendingJobs() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "queue_jobs` WHERE `status` = 'pending' OR (`status` = 'failed' AND `attempts` < '" . (int)$this->max_attempts . "')");
        return $query->rows;
    }

    public function markJobAsProcessing($id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "queue_jobs` SET `status` = 'processing' WHERE `id` = '" . (int)$id . "'");
    }

    public function markJobAsDone($id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "queue_jobs` SET `status` = 'done' WHERE `id` = '" . (int)$id . "'");
    }

    public function markJobAsFailed($id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "queue_jobs` SET `status` = 'failed', `attempts` = `attempts` + 1 WHERE `id` = '" . (int)$id . "'");
    }
}
