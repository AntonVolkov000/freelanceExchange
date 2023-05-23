<?php

class Employer
{
    private Database $database;
    private string $NO_LOGIN_EMPLOYER;
    private string $HOW_CREATE_TASK_EMPLOYER = 'Для создания задания используйте команду /create_task';
    private string $CREATE_TASK_TEXT = 'Для создания задания заполните его поля.' . PHP_EOL . PHP_EOL . 'Команды для заполнения полей: ' . PHP_EOL . '/add_brief_description текст - для добавления краткого описания задания' . PHP_EOL . '/add_full_description текст - для добавления полного описания задания' . PHP_EOL . '/add_task_price цена - для добавления цены задания' . PHP_EOL . '/add_task_completing_deadline дата - для добавления дедлайна задания. Дата в формате "год-месяц-день часы:минуты:секунды"' . PHP_EOL . '/add_bank_account номер_счёта - для добавления банковского счёта, с которого будут списаны средства. Длина номера 20 цифр' . PHP_EOL . '/add_task_topic_id - для добавления темы задания';
    private int $chat_id = 0;
    private int $employer_id;
    private array $employerChatIds;
    private string $brief_description = '';
    private string $full_description = '';
    private int $task_price = 0;
    private string $task_deadline = '';
    private string $bank_account = '';
    private int $task_topic = 0;
    private array $taskTopicArray;
    private string $taskTopicList;

    public function __construct($database, $employer_id, $employerChatIds, $employerChatIdsStr, $taskTopicArray, $taskTopicList)
    {
        $this->database = $database;
        $this->employer_id = $employer_id;
        $this->employerChatIds = $employerChatIds;
        $this->taskTopicArray = $taskTopicArray;
        $this->taskTopicList = $taskTopicList;
        $this->NO_LOGIN_EMPLOYER = 'Вы не авторизованы, для подключения используйте команду /start_employer chat_id' . PHP_EOL . PHP_EOL . 'Доступные chat_id для работодателя:' . PHP_EOL . PHP_EOL . $employerChatIdsStr;
    }

    public function commandHandler($freelance_request, $chat_id): void
    {
        global $prevCommand;

        $this->chat_id = $chat_id;

        $allFill = $prevCommand == '/create_task' && ($freelance_request[0] == '/create_task' || $freelance_request[0] == '/start_employer') && ($this->brief_description != '' || $this->full_description != '' || $this->task_price != 0 || $this->bank_account != '' || $this->task_deadline != '' || $this->task_topic != 0);
        $notAllFill = $prevCommand == '/create_task' && $freelance_request[0] == '/confirm_task' && ($this->brief_description == '' || $this->full_description == '' || $this->task_price == 0 || $this->bank_account == '' || $this->task_deadline == '' || $this->task_topic == 0);

        if ($allFill || $notAllFill) {
            $taskPriceStr = $this->task_price == 0 ? '' : $this->task_price;
            $taskTopicStr = $this->task_topic == 0 ? '' : $this->taskTopicArray[$this->task_topic];
            sendMessage($chat_id, 'Вы создавали задание, заполните все обязательные поля и подтвердите его командой /confirm_task или отмените создание задания командой /cancel_task' . PHP_EOL . PHP_EOL . 'Краткое описание: ' . $this->brief_description . PHP_EOL . 'Полное описание: ' . $this->full_description . PHP_EOL . 'Цена задания: ' . $taskPriceStr  . PHP_EOL . 'Номер счёта: ' . $this->bank_account . PHP_EOL . 'Дедлайн задания: ' . $this->task_deadline . PHP_EOL . 'Тема задания: ' . $taskTopicStr);
        } else {
            switch ($freelance_request[0]) {
                case '/start_employer':
                    $this->startEmployer($freelance_request);
                    break;
                case '/create_task':
                    $this->createTask($freelance_request);
                    break;
                case '/add_brief_description':
                    $this->addBriefDescription($freelance_request);
                    break;
                case '/add_full_description':
                    $this->addFullDescription($freelance_request);
                    break;
                case '/add_task_price':
                    $this->addTaskPrice($freelance_request);
                    break;
                case '/add_task_completing_deadline':
                    $this->addTaskDeadline($freelance_request);
                    break;
                case '/add_bank_account':
                    $this->addBankAccount($freelance_request);
                    break;
                case '/add_task_topic_id':
                    $this->addTaskTopic($freelance_request);
                    break;
                case '/confirm_task':
                    $this->confirmTask($freelance_request);
                    break;
                case '/cancel_task':
                    $this->cancelTask($freelance_request);
                    break;
                default:
                    sendMessage($chat_id,
                        'Такой команды не существует, для получения информации о том как пользоваться ботом воспользуйтесь командой /start. Для получения информации о всех командах, воспользуйтесь командой /help');
            }
        }
    }

    private function startEmployer($freelance_request): void
    {
        global $file, $json, $prevCommand;

        if (count($freelance_request) == 2) {
            if (is_numeric($freelance_request[1])) {
                if (in_array($freelance_request[1], $this->employerChatIds)) {
                    $prevCommand = '/start_employer';

                    $employer_id = $freelance_request[1];
                    $json['employerId'] = $employer_id;
                    $newJsonString = json_encode($json);
                    file_put_contents($file, $newJsonString);

                    sendMessage($this->chat_id,
                        SUCCESS_CONNECT . ' к работодателю с chat_id равным ' . $employer_id . PHP_EOL . PHP_EOL . $this->HOW_CREATE_TASK_EMPLOYER);
                } else {
                    sendMessage($this->chat_id, 'Для работодателя ' . INCORRECT_CHAT_ID);
                }
            } else {
                sendMessage($this->chat_id, INCORRECT_INT);
            }
        } else {
            sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
        }
    }

    private function createTask($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if (count($freelance_request) == 1) {
                $prevCommand = '/create_task';
                sendMessage($this->chat_id, $this->CREATE_TASK_TEXT);
            } else {
                sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function addBriefDescription($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) > 1) {
                    $this->brief_description = mergeArguments($freelance_request);
                    sendMessage($this->chat_id, 'Вы успешно добавили краткое описание задания');
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function addFullDescription($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) > 1) {
                    $this->full_description = mergeArguments($freelance_request);
                    sendMessage($this->chat_id, 'Вы успешно добавили полное описание задания');
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function addTaskPrice($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 2) {
                    if (is_numeric($freelance_request[1])) {
                        $this->task_price = (int)$freelance_request[1];
                        sendMessage($this->chat_id, 'Вы успешно добавили цену задания');
                    } else {
                        sendMessage($this->chat_id, INCORRECT_INT);
                    }
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function addTaskDeadline($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 2) {
                    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/m', $freelance_request[1])) {
                        if ($this->checkMinimumDate($freelance_request[1])) {
                            $this->task_deadline = $freelance_request[1];
                            sendMessage($this->chat_id, 'Вы успешно добавили дедлайн задания');
                        } else {
                            sendMessage($this->chat_id,
                                'Дата ближайшего дедалайна - завтрашний день');
                        }
                    } else {
                        sendMessage($this->chat_id,
                            'Некорректный формат даты, должен быть "год-месяц-день"' . PHP_EOL . 'Пример: 2023-01-01');
                    }
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function checkMinimumDate($deadline): bool
    {
        $dateDeadline = explode('-', explode(' ', $deadline)[0]);
        $yearDeadline = (int)$dateDeadline[0];
        $monthDeadline = (int)$dateDeadline[1];
        $dayDeadline = (int)$dateDeadline[2];
        $dateNow = explode('-', explode(' ', date('Y-m-d'))[0]);
        $yearNow = (int)$dateNow[0];
        $monthNow = (int)$dateNow[1];
        $dayNow = (int)$dateNow[2];
        $afterYyear = $yearDeadline > $yearNow;
        $afterMonth = $yearDeadline == $yearNow && $monthDeadline > $monthNow;
        $afterDay = $yearDeadline == $yearNow && $monthDeadline == $monthNow && $dayDeadline > $dayNow;
        return $afterYyear || $afterMonth || $afterDay;
    }

    private function addBankAccount($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 2) {
                    if (is_numeric($freelance_request[1])) {
                        if (strlen($freelance_request[1]) == 20) {
                            $this->bank_account = $freelance_request[1];

                            sendMessage($this->chat_id, 'Вы успешно добавили номер банковского счёта');
                        } else {
                            sendMessage($this->chat_id, 'Длина номера счёта должна быть равной 20');
                        }
                    } else {
                        sendMessage($this->chat_id, INCORRECT_INT);
                    }
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function addTaskTopic($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 2) {
                    if (is_numeric($freelance_request[1])) {
                        if ($freelance_request[1] >= 1 && $freelance_request[1] <= count($this->taskTopicArray)) {
                            $this->task_topic = (int)$freelance_request[1];
                            sendMessage($this->chat_id, 'Вы успешно добавили тему задания');
                        } else {
                            sendMessage($this->chat_id,
                                'Номер темы не входит в диапазон от 1 до ' . count($this->taskTopicArray));
                        }
                    } else {
                        sendMessage($this->chat_id, INCORRECT_INT);
                    }
                } else {
                    if (count($freelance_request) == 1) {
                        sendMessage($this->chat_id,
                            'Для добавления темы введите команду /add_task_topic_id номер_темы' . PHP_EOL . PHP_EOL . 'Темы:' . $this->taskTopicList);
                    } else {
                        sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                    }
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function confirmTask($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 1) {
                    $employer_db_id = array_search($this->employer_id, $this->employerChatIds);
                    $created_at = date('Y-m-d H:i:s');
                    $bank_account_id = mysqli_fetch_array($this->database->getBankAccountId($this->bank_account), MYSQLI_ASSOC)['id'];

                    $this->database->insertTask($this->brief_description, $this->full_description, $this->task_price, $created_at, $this->task_deadline, '', $employer_db_id + 1, $bank_account_id, 1, $this->task_topic);

                    $taskPriceStr = $this->task_price == 0 ? '' : $this->task_price;
                    $taskTopicStr = $this->task_topic == 0 ? '' : $this->taskTopicArray[$this->task_topic];

                    sendMessage($this->chat_id,
                        'Вы успешно разместили задание на фриланс-платформе' . PHP_EOL . PHP_EOL . 'Краткое описание: ' . $this->brief_description . PHP_EOL . 'Полное описание: ' . $this->full_description . PHP_EOL . 'Цена задания: ' . $taskPriceStr . PHP_EOL . 'Дедлайн задания: ' . $this->task_deadline . PHP_EOL . 'Номер счёта: ' . $this->bank_account . PHP_EOL . 'Тема задания: ' . $taskTopicStr);
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }

    private function cancelTask($freelance_request): void
    {
        global $prevCommand;

        if ($this->employer_id != 0) {
            if ($prevCommand == '/create_task') {
                if (count($freelance_request) == 1) {
                    $this->brief_description = '';
                    $this->full_description = '';
                    $this->task_price = 0;
                    $this->task_deadline = '';
                    $this->task_topic = 0;
                    sendMessage($this->chat_id, 'Вы отменили создание задания, все заполненные данные удалены');
                } else {
                    sendMessage($this->chat_id, INCORRECT_ARGUMENTS_NUMBER);
                }
            } else {
                sendMessage($this->chat_id, $this->HOW_CREATE_TASK_EMPLOYER);
            }
        } else {
            sendMessage($this->chat_id, $this->NO_LOGIN_EMPLOYER);
        }
    }
}
