<?php
class TicketModel {
    private $db;
    private $table = 'tickets';

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT id, event_id, type, price, available_quantity FROM $this->table");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, event_id, type, price, available_quantity FROM $this->table WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO $this->table (event_id, type, price, available_quantity)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['event_id'],
            $data['type'],
            $data['price'],
            $data['available_quantity']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE $this->table 
            SET event_id = ?, type = ?, price = ?, available_quantity = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['event_id'],
            $data['type'],
            $data['price'],
            $data['available_quantity'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM $this->table WHERE id = ?");
        return $stmt->execute([$id]);
    }
}