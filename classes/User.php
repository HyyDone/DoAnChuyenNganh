<?php
class User
{
    private $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function find($id)
    {
        $stmt = $this->pdo->prepare('SELECT id,username,email,full_name,avatar,role FROM users WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
