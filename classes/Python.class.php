<?php

class Python {

    private $python_path;
    private $game_path;
    private $path;
    private $file;
    private $args;
    private $queries = 0;
    
    function __construct(){
        
        $this->python_path = '/usr/bin/env python3';
        $this->game_path = __DIR__ . '/../';
        $this->args = '';
        $this->log = ' > /home/runner/workspace/.mysql/run/python.log 2>&1';
        
    }
    
    public function add_badge($userID, $badgeID, $clan = ''){
        
        if($clan == ''){
            $userBadge = 'user';
        } else {
            $userBadge = 'clan';
        }
        
        $this->path = 'python/';
        $this->file = 'badge_add.py';
        $this->args = escapeshellarg($userBadge).' '.escapeshellarg($userID).' '.escapeshellarg($badgeID);
        $this->queries = 15;

        self::call();
        
    }
    
    public function generateProfile($id, $l = 'en'){
        
        $this->path = 'python/';
        $this->file = 'profile_generator.py';
        $this->args = escapeshellarg($id).' '.escapeshellarg($l);
        $this->queries = 20;

        self::call();

    }
    
    public function createUser($username, $password, $email, $ip, $facebook = 0, $social_network = ''){

        $pdo = PDO_DB::factory();

        $gamePass = substr(bin2hex(random_bytes(4)), 0, 8);
        $ipLong = sprintf('%u', ip2long($ip));

        if($password === 0 || $password === '0' || $password === ''){
            require_once __DIR__ . '/BCrypt.class.php';
            $bcrypt = new BCrypt();
            $password = $bcrypt->hash(bin2hex(random_bytes(16)));
        }

        $pdo->beginTransaction();

        try {

            $stmt = $pdo->prepare("INSERT INTO users (login, password, gamePass, email, gameIP, realIP, homeIP, learning, premium, lastLogin) VALUES (:login, :password, :gamePass, :email, :gameIP, :realIP, :homeIP, 0, 0, NOW())");
            $stmt->execute(array(
                ':login' => $username,
                ':password' => $password,
                ':gamePass' => $gamePass,
                ':email' => $email,
                ':gameIP' => $ipLong,
                ':realIP' => $ipLong,
                ':homeIP' => $ipLong,
            ));

            $userID = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO hardware (userID, name) VALUES (:userID, 'Server #1')")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO log (userID, text, isNPC) VALUES (:userID, :text, 0)")->execute(array(
                ':userID' => $userID,
                ':text' => date('Y-m-d H:i') . ' - localhost installed current operating system',
            ));
            $pdo->prepare("INSERT INTO cache (userID) VALUES (:userID)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO cache_profile (userID) VALUES (:userID)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO hist_users_current (userID, user, reputation, age, clanID, clanName, timePlaying, missionCount, hackCount, ddosCount, ipResets, moneyEarned, moneyTransfered, moneyHardware, moneyResearch, warezSent, spamSent, bitcoinSent, profileViews) VALUES (:userID, :user, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)")->execute(array(
                ':userID' => $userID,
                ':user' => $username,
            ));
            $pdo->prepare("INSERT INTO ranking_user (userID, rank) VALUES (:userID, -1)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO certifications (userID, certLevel) VALUES (:userID, 0)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO users_puzzle (userID) VALUES (:userID)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO users_learning (userID) VALUES (:userID)")->execute(array(':userID' => $userID));
            $pdo->prepare("INSERT INTO users_language (userID) VALUES (:userID)")->execute(array(':userID' => $userID));

            if($facebook > 0 && $social_network == 'facebook'){
                $pdo->prepare("INSERT INTO users_facebook (userID, gameID) VALUES (:userID, :facebook)")->execute(array(':userID' => $userID, ':facebook' => $facebook));
            } elseif($facebook > 0 && $social_network == 'twitter'){
                $pdo->prepare("INSERT INTO users_twitter (userID, gameID) VALUES (:userID, :twitter)")->execute(array(':userID' => $userID, ':twitter' => $facebook));
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Python::createUser failed: ' . $e->getMessage());
        }

    }
    
    private function call(){
                
        exec($this->python_path.' '.$this->game_path.$this->path.$this->file.' '.$this->args.$this->log);
        exec($this->python_path.' '.$this->game_path.$this->path.'query_counter.py '.$this->queries);
                
    }
    
}

?>
