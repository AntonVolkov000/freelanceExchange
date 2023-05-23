<?php

class Admin
{
    private Database $database;
    private string $NO_LOGIN_ADMIN;
    private string $HOW_CHECK_TASK = 'Для просмотра непроверенных заданий используйте команду /admin_show_tasks';
    private string $HOW_SELECT_TASK_ADMIN = 'Для выбора задания используйте команду /admin_select_task номер_задания' . PHP_EOL;
    private string $HOW_CHECK_TASK_ADMIN = 'Для проверки задания используйте команды: ' . PHP_EOL . PHP_EOL . '/verification_successful - для успешной проверки задания' . PHP_EOL . '/verification_failed комментарий - для отправки задания на доработку с комментарием';
    public int $chat_id = 0;
    public int $admin_id = 0;
    private array $adminChatIds;
    private int $selectedTaskId;
    private array $unverifiedTasks;
    private array $unverifiedTaskIds;

    public function __construct($database, $admin_id, $adminChatIds, $adminChatIdsStr)
    {
        $this->database = $database;
        $this->admin_id = $admin_id;
        $this->adminChatIds = $adminChatIds;
        $this->NO_LOGIN_ADMIN = 'Вы не авторизованы, для подключения используйте команду /start_admin chat_id' . PHP_EOL . PHP_EOL . 'Доступные chat_id для администратора:' . PHP_EOL . PHP_EOL . $adminChatIdsStr;
    }

    public function commandHandler($freelance_request, $chat_id): void
    {
        $this->chat_id = $chat_id;

        switch ($freelance_request[0]) {
            case '/start_admin':
                $this->startAdmin($freelance_request);
                break;
            case '/admin_show_tasks':
                $this->showTasks($freelance_request);
                break;
            case '/admin_select_task':
                $this->selectTask($freelance_request);
                break;
            case '/verification_successful':
                $this->verificationSuccessful($freelance_request);
                break;
            case '/verification_failed':
                $this->verificationFailed($freelance_request);
                break;
            default:
                sendMessage($chat_id,
                    'Такой команды не существует, для получения информации о том как пользоваться ботом воспользуйтесь командой /start. Для получения информации о всех командах, воспользуйтесь командой /help');
        }
    }

    private function startAdmin($freelance_request): void
    {
        global $file, $json, $prevCommand;

        if (count($freelance_request) == 2) {
            if (is_numeric($freelance_request[1])) {
                if (in_array($freelance_request[1], $this->adminChatIds)) {
                    $prevCommand = '/start_admin';

                    $admin_id = $freelance_request[1];
                    $json['adminId'] = $admin_id;
                    $newJsonString = json_encode($json);
                    file_put_contents($file, $newJsonString);

                    sendMessage($this->chat_id, SUCCESS_CONNECT . ' к администратору с chat_id равным ' . $admin_id . PHP_EOL . PHP_EOL . $this->HOW_CHECK_TASK);
                } else {
                    sendMessage($this->chat_id, 'Для администратора '. INCORRECT_CHAT_ID);
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_INT);
            }
        } else {
            sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
        }
    }

    private function showTasks($freelance_request): void
    {
        if ($this->admin_id != 0) {
            if (count($freelance_request) == 1) {
                $this->unverifiedTasks = mysqliToArrays($this->database->getUnverifiedTasks());
                $unverifiedTasksStr = '';
                $unverifiedTaskIds = [];
                foreach ($this->unverifiedTasks as $task) {
                    $unverifiedTasksStr .=  PHP_EOL . $task['task_id'] . '. ' . $task['brief_description'];
                    $unverifiedTaskIds[] = $task['task_id'];
                }
                $this->unverifiedTaskIds = $unverifiedTaskIds;

                sendMessage($this->chat_id, $this->HOW_SELECT_TASK_ADMIN . $unverifiedTasksStr);
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_ADMIN);
        }
    }

    private function selectTask($freelance_request): void
    {
        global $taskTopicArray;

        if ($this->admin_id != 0) {
            if (count($freelance_request) == 2) {
                if (is_numeric($freelance_request[1])) {
                    if (in_array($freelance_request[1], $this->unverifiedTaskIds)) {
                        $this->selectedTaskId = $freelance_request[1];
                        $selectedTask = [];
                        foreach ($this->unverifiedTasks as $task) {
                            if ($task['task_id'] == $freelance_request[1]) {
                                $selectedTask = $task;
                                break;
                            }
                        }
                        sendMessage($this->chat_id, 'Вы выбрали задание номер ' . $selectedTask['task_id'] . PHP_EOL . PHP_EOL . 'Краткое описание: ' . $selectedTask['brief_description'] . PHP_EOL . 'Полное описание: ' . $selectedTask['full_description'] . PHP_EOL . 'Цена задания: ' . $selectedTask['task_price'] . PHP_EOL . 'Тема задания: ' . $taskTopicArray[$selectedTask['task_topic_id']] . PHP_EOL . PHP_EOL . $this->HOW_CHECK_TASK_ADMIN);
                    } else {
                        sendMessage($this->chat_id, 'Такого номера задания не существует');
                    }
                } else {
                    sendMessage($this->chat_id, INCORRECT_INT);
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_ADMIN);
        }
    }

    private function verificationSuccessful($freelance_request): void
    {
        if ($this->admin_id != 0) {
            if (count($freelance_request) == 1) {
                $this->database->updateTask($this->selectedTaskId, '', 2);
                sendMessage($this->chat_id, 'Вы успешно подтвердили проверку задания');
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_ADMIN);
        }
    }

    private function verificationFailed($freelance_request): void
    {
        if ($this->admin_id != 0) {
            if (count($freelance_request) >= 2) {
                $this->database->updateTask($this->selectedTaskId, mergeArguments($freelance_request), 3);
                sendMessage($this->chat_id, 'Вы успешно отправили задание на доработку');
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_ADMIN);
        }
    }
}
