<?php
class ModelQueueQueue extends Model {
    public function getPendingTasks() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "queue_jobs WHERE status = 'pending' OR (status = 'failed' AND attempts < " . (int)$this->max_attempts . ") order by `id` ASC ");
        return $query->rows;
    }

    public function updateTaskStatus($task_id, $status) {
        // تأكيد أن الحالة قد تم تحديثها بنجاح
        $this->db->query("UPDATE " . DB_PREFIX . "queue_jobs SET status = '" . $this->db->escape($status) . "', updated_at = NOW() WHERE id = '" . (int)$task_id . "' AND (status = 'pending' OR (status = 'failed' AND attempts < " . (int)$this->max_attempts . "))");
        return $this->db->countAffected() > 0;
    }

    public function incrementTaskAttempts($task_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "queue_jobs SET attempts = attempts + 1 WHERE id = '" . (int)$task_id . "'");
    }
}