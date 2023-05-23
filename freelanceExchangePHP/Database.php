<?php

class Database
{
    public mysqli $mysqli;

    public function connectDB($host, $user, $pass, $database): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysqli = new mysqli($host, $user, $pass, $database);
    }

    public function getIdsByRole($role): mysqli_result
    {
        if ($role == 'freelanceexchangeadmin') {
            $columnIdName = 'admin_id';
            $columnChatIdName = 'admin_chat_id';
        } else {
            $columnIdName = $role.'_id';
            $columnChatIdName = $role.'_chat_id';
        }
        $tableName = 'app_'.$role;

        return $this->mysqli->query('SELECT '.$columnIdName.', '.$columnChatIdName.' FROM '.$tableName);
    }

    public function getTaskTopic(): mysqli_result
    {
        return $this->mysqli->query('SELECT * FROM app_tasktopic');
    }

    public function getBankAccountId($bank_account): mysqli_result
    {
        return $this->mysqli->query('SELECT id FROM app_bankaccount WHERE number = "' . $bank_account . '"');
    }

    public function insertTask($brief_description, $full_description, $task_price, $created_at, $task_completing_deadline, $admin_comment, $employer_id, $employer_bank_account_id, $task_status_id, $task_topic_id): void
    {
        $this->mysqli->query('
        INSERT app_task(brief_description, full_description, task_price, created_at, task_completing_deadline, admin_comment, employer_id, employer_bank_account_id, task_status_id, task_topic_id)
        VALUES ("' . $brief_description . '", "' . $full_description . '", ' . $task_price . ', "' . $created_at . '", "' . $task_completing_deadline . '", "' . $admin_comment . '", ' . $employer_id . ', ' . $employer_bank_account_id . ', ' . $task_status_id . ', ' . $task_topic_id . ')
        ');
    }

    public function getUnverifiedTasks(): mysqli_result
    {
        return $this->mysqli->query('SELECT * FROM app_task WHERE task_status_id = 1');
    }

    public function updateTask($task_id, $admin_comment, $task_status_id): void
    {
        $this->mysqli->query('
        UPDATE app_task
        SET admin_comment = "' . $admin_comment . '",
        task_status_id = ' . $task_status_id . '
        WHERE task_id = ' . $task_id . '
        ');
    }

    public function getTasksByTopicId($task_topic_id): mysqli_result
    {
        return $this->mysqli->query('SELECT * FROM app_task WHERE task_topic_id = ' . $task_topic_id . ' AND task_status_id = 2');
    }

    public function insertResponse($freelancer_comment, $created_at, $freelancer_id, $freelancer_bank_account_id, $response_status_id, $task_id): void
    {
        $this->mysqli->query('
        INSERT app_response(freelancer_comment, created_at, freelancer_id, freelancer_bank_account_id, response_status_id, task_id)
        VALUES ("' . $freelancer_comment . '", "' . $created_at . '", ' . $freelancer_id . ', ' . $freelancer_bank_account_id . ', ' . $response_status_id . ', ' . $task_id . ')'
        );
    }
}