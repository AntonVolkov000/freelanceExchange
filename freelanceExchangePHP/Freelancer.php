<?php

class Freelancer
{
    private Database $database;
    private string $NO_LOGIN_FREELANCER;
    private string $HOW_CHOOSE_TASK = 'Для просмотра заданий по теме используйте команду /show_tasks_by_topic номер_темы';
    private string $HOW_SELECT_TASK = 'Для выбора задания используйте команду /select_task номер_задания';
    private string $HOW_SUBMIT_RESPONSE = 'Для отправки отклика на задание используйте команды: ' . PHP_EOL . PHP_EOL . '/submit_response номер_банковского_счёта - для отправки отклика без комментария' . PHP_EOL . '/offer_terms номер_банковского_счёта комментарий - для отправки отклика с комментарием';
    public int $chat_id = 0;
    public int $freelancer_id = 0;
    private array $freelancerChatIds;
    private array $tasksByTopic;
    private array $taskByTopicIds;
    private int $selectedTaskId;

    public function __construct($database, $freelancer_id, $freelancerChatIds, $freelancerChatIdsStr)
    {
        $this->database = $database;
        $this->freelancer_id = $freelancer_id;
        $this->freelancerChatIds = $freelancerChatIds;
        $this->NO_LOGIN_FREELANCER = 'Вы не авторизованы, для подключения используйте команду /start_freelancer chat_id' . PHP_EOL . PHP_EOL . 'Доступные chat_id для фрилансера:' . PHP_EOL . PHP_EOL . $freelancerChatIdsStr;
    }

    public function commandHandler($freelance_request, $chat_id): void
    {
        $this->chat_id = $chat_id;

        switch ($freelance_request[0]) {
            case '/start_freelancer':
                $this->startFreelancer($freelance_request);
                break;
            case '/show_tasks_by_topic':
                $this->showTasks($freelance_request);
                break;
            case '/select_task':
                $this->selectTask($freelance_request);
                break;
            case '/submit_response':
                $this->submitResponse($freelance_request);
                break;
            case '/offer_terms':
                $this->offerTerms($freelance_request);
                break;
            default:
                sendMessage($chat_id,
                    'Такой команды не существует, для получения информации о том как пользоваться ботом воспользуйтесь командой /start. Для получения информации о всех командах, воспользуйтесь командой /help');
        }
    }

    private function startFreelancer($freelance_request): void
    {
        global $file, $json, $prevCommand, $taskTopicList;

        if (count($freelance_request) == 2) {
            if (is_numeric($freelance_request[1])) {
                if (in_array($freelance_request[1], $this->freelancerChatIds)) {
                    $prevCommand = '/start_freelancer';

                    $freelancer_id = $freelance_request[1];
                    $json['freelancerId'] = $freelancer_id;
                    $newJsonString = json_encode($json);
                    file_put_contents($file, $newJsonString);

                    sendMessage($this->chat_id, SUCCESS_CONNECT . ' к фрилансеру с chat_id равным ' . $freelancer_id . PHP_EOL . PHP_EOL . $this->HOW_CHOOSE_TASK . PHP_EOL . $taskTopicList);
                } else {
                    sendMessage($this->chat_id, 'Для фрилансера '. INCORRECT_CHAT_ID);
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
        global $taskTopicArray;

        if ($this->freelancer_id != 0) {
            if (count($freelance_request) == 2) {
                if (is_numeric($freelance_request[1])) {
                    if ($freelance_request[1] >= 1 && $freelance_request[1] <= count($taskTopicArray)) {
                        $this->tasksByTopic = mysqliToArrays($this->database->getTasksByTopicId($freelance_request[1]));

                        $tasksByTopicStr = '';
                        $taskByTopicIds = [];
                        foreach ($this->tasksByTopic as $task) {
                            $tasksByTopicStr .=  PHP_EOL . $task['task_id'] . '. ' . $task['brief_description'];
                            $taskByTopicIds[] = $task['task_id'];
                        }
                        $this->taskByTopicIds = $taskByTopicIds;

                        if ($tasksByTopicStr == '') {
                            $tasksByTopicStr = PHP_EOL . 'По выбранное теме заданий не найдено';
                        } else {
                            $tasksByTopicStr = PHP_EOL . $this->HOW_SELECT_TASK . PHP_EOL . $tasksByTopicStr;
                        }

                        sendMessage($this->chat_id, 'Вы выбрали тему "' . $taskTopicArray[$freelance_request[1]] . '"' . PHP_EOL . $tasksByTopicStr);
                    } else {
                        sendMessage($this->chat_id,
                            'Номер темы не входит в диапазон от 1 до ' . count($taskTopicArray));
                    }
                } else {
                    sendMessage($this->chat_id, INCORRECT_INT);
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_FREELANCER);
        }
    }

    private function selectTask($freelance_request): void
    {
        global $taskTopicArray;

        if ($this->freelancer_id != 0) {
            if (count($freelance_request) == 2) {
                if (is_numeric($freelance_request[1])) {
                    if (in_array($freelance_request[1], $this->taskByTopicIds)) {
                        $this->selectedTaskId = $freelance_request[1];
                        $selectedTask = [];
                        foreach ($this->tasksByTopic as $task) {
                            if ($task['task_id'] == $freelance_request[1]) {
                                $selectedTask = $task;
                                break;
                            }
                        }
                        sendMessage($this->chat_id, 'Вы выбрали задание номер ' . $selectedTask['task_id'] . PHP_EOL . PHP_EOL . 'Краткое описание: ' . $selectedTask['brief_description'] . PHP_EOL . 'Полное описание: ' . $selectedTask['full_description'] . PHP_EOL . 'Цена задания: ' . $selectedTask['task_price'] . PHP_EOL . 'Тема задания: ' . $taskTopicArray[$selectedTask['task_topic_id']] . PHP_EOL . PHP_EOL . $this->HOW_SUBMIT_RESPONSE);
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
            sendMessage($this->chat_id, $this->NO_LOGIN_FREELANCER);
        }
    }

    private function submitResponse($freelance_request): void
    {
        if ($this->freelancer_id != 0) {
            if (count($freelance_request) == 2) {
                if (is_numeric($freelance_request[1])) {
                    if (strlen($freelance_request[1]) == 20) {
                        $created_at = date('Y-m-d H:i:s');

                        $freelancer_db_id = array_search($this->freelancer_id, $this->freelancerChatIds);
                        $bank_account_id = mysqli_fetch_array($this->database->getBankAccountId($freelance_request[1]), MYSQLI_ASSOC)['id'];

                        $this->database->insertResponse('', $created_at, $freelancer_db_id, $bank_account_id, 1, $this->selectedTaskId);

                        sendMessage($this->chat_id, 'Вы успешно отправили отклик на задание без комментария');
                    } else {
                        sendMessage($this->chat_id, 'Длина номера счёта должна быть равной 20');
                    }
                } else {
                    sendMessage($this->chat_id, 'Номер банковского счёта должен быть числом');
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_FREELANCER);
        }
    }

    private function offerTerms($freelance_request): void
    {
        if ($this->freelancer_id != 0) {
            if (count($freelance_request) >= 3) {
                if (is_numeric($freelance_request[1])) {
                    if (strlen($freelance_request[1]) == 20) {
                        $created_at = date('Y-m-d H:i:s');

                        $freelancer_db_id = array_search($this->freelancer_id, $this->freelancerChatIds);
                        $bank_account_id = mysqli_fetch_array($this->database->getBankAccountId($freelance_request[1]), MYSQLI_ASSOC)['id'];

                        $this->database->insertResponse($freelance_request[2], $created_at, $freelancer_db_id, $bank_account_id, 1, $this->selectedTaskId);

                        sendMessage($this->chat_id, 'Вы успешно отправили отклик на задание с комментарием');
                    } else {
                        sendMessage($this->chat_id, 'Длина номера счёта должна быть равной 20');
                    }
                } else {
                    sendMessage($this->chat_id, 'Номер банковского счёта должен быть числом');
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_FREELANCER);
        }
    }
}
