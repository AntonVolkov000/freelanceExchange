<?php

include('vendor/autoload.php');
include('Database.php');
include('Employer.php');
include('Admin.php');
include('Freelancer.php');
use Telegram\Bot\Api;
function printT($text): void
{
    echo '<pre>';
    print_r($text);
    echo '</pre>';
}

$telegram = new Api('');

$file = 'additional_data.json';

// php -S 127.0.0.1:8000 Запуск веб сервера
// php .\index.php Запуск php скрипта в консоли

date_default_timezone_set('Asia/Yekaterinburg');

$json = json_decode(file_get_contents($file), true);
$prevDate = $json['prevDate'];
$employer_id = $json['employerId'];
$admin_id = $json['adminId'];
$freelancer_id = $json['freelancerId'];

$prevCommand = '';

$host='localhost';
$user='root';
$pass='root';
$databaseName='pis_bot_users';

$database = new Database();

function mysqliToArrayIds($result, $role): array
{
    $columnIdName = $role.'_id';
    $columnChatIdName = $role.'_chat_id';
    $array = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $array[$row[$columnIdName]] = $row[$columnChatIdName];
    }
    return $array;
}

function mysqliToArrays($result): array
{
    $array = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $array[] = $row;
    }
    return $array;
}

$database->connectDB($host, $user, $pass, $databaseName);

$employerChatIds = mysqliToArrayIds($database->getIdsByRole('employer'), 'employer');
$adminChatIds = mysqliToArrayIds($database->getIdsByRole('freelanceexchangeadmin'), 'admin');
$freelancerChatIds = mysqliToArrayIds($database->getIdsByRole('freelancer'), 'freelancer');

$employerChatIdsStr = implode(PHP_EOL, $employerChatIds);
$adminChatIdsStr = implode(PHP_EOL, $adminChatIds);
$freelancerChatIdsStr = implode(PHP_EOL, $freelancerChatIds);

define("START_TEXT",
    'Для подключения к аккаунту вы можете использовать команды:' . PHP_EOL . '/start_employer chat_id' . PHP_EOL . '/start_admin chat_id' . PHP_EOL . '/start_freelancer chat_id' . PHP_EOL . PHP_EOL . 'Доступные chat_id для работодателя:' . PHP_EOL . $employerChatIdsStr . PHP_EOL . PHP_EOL . 'Доступные chat_id для администратора:' . PHP_EOL . $adminChatIdsStr . PHP_EOL . PHP_EOL . 'Доступные chat_id для фрилансера:' . PHP_EOL . $freelancerChatIdsStr);
const HELP_TEXT = 'Все команды:' . PHP_EOL . PHP_EOL . 'Авторизация аккаунта:' . PHP_EOL . '/start_employer chat_id' . PHP_EOL . '/start_admin chat_id' . PHP_EOL . '/start_freelancer chat_id' . PHP_EOL . PHP_EOL . 'Команды для работодателя:' . PHP_EOL . '/create_task - режим создания задания' . PHP_EOL . '/add_brief_description текст - добавить краткое описание задания' . PHP_EOL . '/add_full_description текст - добавить полное описание задания' . PHP_EOL . '/add_task_price цена - добавить цену задания' . PHP_EOL . '/add_task_completing_deadline дата - добавить дедлайн задания. Дата в формате "год-месяц-день часы:минуты:секунды"' . PHP_EOL . '/add_bank_account номер_счёта - добавление номера банковского счёта, с которого будут списаны средства. Длина номера 20 цифр' . PHP_EOL . '/add_task_topic_id - добавление темы задания' . PHP_EOL . '/confirm_task - подтвердить создание задания' . PHP_EOL . '/cancel_task - отменить создание задания' . PHP_EOL . PHP_EOL . 'Команды для администратора:' . PHP_EOL . '/admin_show_tasks - получение непроверенных заданий' . PHP_EOL . '/admin_select_task номер_задания - просмотр полной информации о задании'  . PHP_EOL . '/verification_successful - подтвердить успешную проверку' . PHP_EOL . '/verification_failed комментарий - отправить задания на доработку с комментарием' . PHP_EOL . PHP_EOL . 'Команды для фрилансера:' . PHP_EOL . '/show_tasks_by_topic тема - посмотреть задания по теме' . PHP_EOL . '/select_task номер_задания - выбрать задание для ознакомления' . PHP_EOL . '/submit_response номер_банковского_счёта - отправить заявку без комментария' . PHP_EOL . '/offer_terms номер_банковского_счёта комментарий - отправить заявку с комментарием';
const INCORRECT_ARGUMENTS_NUMBER = 'Некорректное количество аргументов';
const INCORRECT_INT = 'В качестве аргумента должно быть число';
const INCORRECT_CHAT_ID = 'такого chat_id не существует';
const SUCCESS_CONNECT = 'Вы успешно подключились';

$taskTopicName = array(
    'CSW' => 'Создание и настройка сайтов',
    'L' => 'Верстка',
    'DP' => 'Десктоп программирование',
    'SB' => 'Скрипты и боты',
    'MA' => 'Мобильные приложения',
    'G' => 'Игры',
    'SH' => 'Сервера и хостинг',
    'UTH' => 'Юзабилити, тесты и помощь',
    'LB' => 'Логотип и брендинг',
    'PI' => 'Презентации и инфографика',
    'AI' => 'Арт и иллюстрации',
    'WMD' => 'Веб и мобильный дизайн',
    'MSM' => 'Маркетплейсы и соцсети',
    'IE' => 'Интерьер и экстерьер',
    'PE' => 'Обработка и редактирование',
    'OD' => 'Наружная реклама',
    'PA' => 'Персональный помощник',
    'AT' => 'Бухгалтерия и налоги',
    'CS' => 'Обзвоны и продажи',
    'LA' => 'Юридическая помощь',
    'RP' => 'Подбор персонала',
    'TC' => 'Обучение и консалтинг',
    'CR' => 'Стройка и ремонт'
);

$allTaskTopic = $database->getTaskTopic();
$taskTopicList = '';
$taskTopicArray = [];
while ($row = mysqli_fetch_array($allTaskTopic, MYSQLI_ASSOC)) {
    $taskTopicList .= PHP_EOL . $row['task_topic_id'] . '. ' . $taskTopicName[$row['task_topic_name']];
    $taskTopicArray[$row['task_topic_id']] = $taskTopicName[$row['task_topic_name']];
}

function sendMessage($chat_id, $text): void
{
    global $telegram;
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $text]);
}

function mergeArguments($freelance_request): string
{
    $text = '';
    $arrayLength = count($freelance_request);
    for ($i = 1; $i < $arrayLength; $i++) {
        $text .= $freelance_request[$i];
        if ($i != $arrayLength - 1) {
            $text .= ' ';
        }
    }
    return $text;
}

$employer = new Employer($database, $employer_id, $employerChatIds, $employerChatIdsStr, $taskTopicArray, $taskTopicList);
$admin = new Admin($database, $admin_id, $adminChatIds, $adminChatIdsStr);
$freelancer = new Freelancer($database, $freelancer_id, $freelancerChatIds, $freelancerChatIdsStr);

function commandHandler($freelance_request, $chat_id): void
{
    global $prevCommand;
    global $employer, $admin, $freelancer;

    if ($freelance_request[0] == '/start') {
        sendMessage($chat_id, START_TEXT);
    } elseif ($freelance_request[0] == '/help') {
        sendMessage($chat_id, HELP_TEXT);
    } elseif ($freelance_request[0] == '/start_employer') {
        $employer->commandHandler($freelance_request, $chat_id);
    } elseif ($freelance_request[0] == '/start_admin') {
        $admin->commandHandler($freelance_request, $chat_id);
    } elseif ($freelance_request[0] == '/start_freelancer') {
        $freelancer->commandHandler($freelance_request, $chat_id);
    } elseif ($prevCommand == '/start_employer' || $prevCommand == '/create_task') {
        $employer->commandHandler($freelance_request, $chat_id);
    } elseif ($prevCommand == '/start_admin') {
        $admin->commandHandler($freelance_request, $chat_id);
    } elseif ($prevCommand == '/start_freelancer') {
        $freelancer->commandHandler($freelance_request, $chat_id);
    } else {
        sendMessage($chat_id, 'Такой команды не существует, для получения информации о доступных chat_id воспользуйтесь командой /start. Для получения информации о всех командах воспользуйтесь командой /help');
    }
}

while (true) {
    $requests = $telegram->getUpdates(['offset' => -5]);

    foreach ($requests as $request) {
        if ($request['message'] && $request['message']['date'] > $prevDate) {
            $text = $request['message']['text'];
            $freelance_request = explode(' ', $text);
            $chat_id = $request['message']['chat']['id'];

            if (count($freelance_request) > 0) {
                commandHandler($freelance_request, $chat_id);
            } else {
                sendMessage($chat_id, 'Внутренняя ошибка программы');
            }

            $prevDate = $request['message']['date'];
            $json['prevDate'] = $prevDate;
            $newJsonString = json_encode($json);
            file_put_contents($file, $newJsonString);
        }
    }

    sleep(1);
}
