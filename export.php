<?php

/**
 * Получает параметры экспорта и отправляет их в класс формирования ведомости
 *
 */



// сначала надо подключать конфиг-файл!
require('../../config.php');
require_once $CFG->dirroot . '/report/report_pstgu_deanery/lib.php';
require_once($CFG->libdir . '/adminlib.php');

//defined('MOODLE_INTERNAL') || die;

// запросили ид курса и юзеров
$courseid = required_param('id', PARAM_INT);
echo '<input type="hidden" name="id" value="'.$courseid.'">';
$users =  optional_param_array('users', null, PARAM_INT);
$actselect = optional_param('actselect', null, PARAM_INT);
$cohortid = null;
if($actselect == 3 || $actselect == 4)
    $cohortid = optional_param('cohortmenu', null, PARAM_INT);

//проверка безопасности в контексте системы
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('report/report_pstgu_deanery:view', $context);

//берем курс и контекст курса
if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('nocourseid');
}
require_login($course);

$systemcontext = context_system::instance();
$isfrontpage = ($course->id == SITEID);

$frontpagectx = context_course::instance(SITEID);

if ($isfrontpage) {
    $PAGE->set_pagelayout('admin');
    require_capability('moodle/site:viewparticipants', $systemcontext);
} else {
    $PAGE->set_pagelayout('incourse');
    require_capability('moodle/course:viewparticipants', $context);
}
//извлекаем ид юзеров
if($users)
{
    $keys = array_keys($users);
    $userids = '';
    //делаем предложение IN (?,? ....)
    list($userids, $params) = $DB->get_in_or_equal($keys);
}
else
{
    //echo '<h5>Выберете пользователей!</h5><br>';
    echo $OUTPUT->notification('Выберете пользователей!', 'error');
    redirect(new moodle_url('/report/report_pstgu_deanery/index.php', array('id' => $courseid)));
}
//заголовок страницы
$PAGE->set_title("$course->shortname: ".get_string('pluginname', 'report_report_pstgu_deanery'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
echo $OUTPUT->header();
//проверку прошли, можно освободить переменную
unset($course);

$PAGE->set_url('/report/report_pstgu_deanery/export.php', array('id'=>$courseid));

//получили структуру с полями пользователя
$res = GetUserRecordById($userids, $courseid, $params);
//получили инфо о том, кто произвел данный экспорт
$creator =  $DB->get_record('user', array('id' => $USER->id));
if(!is_null($cohortid) && ($actselect == 3 || $actselect == 4))
{
    $cipherProg = $DB->get_record('cohort', array('id' => $cohortid));    
    $cntuser = count($params);
    if($actselect == 3)
    {
        $ans = AddUserToCohort($cohortid, $params);
    }
    elseif($actselect == 4)
    {
        DeleteUserFromCohort($cohortid, $params);
    }
    // Valid types for notification.
    // 'success' зеленая        
    // 'info' синяя    
    // 'warning' желтая     
    // 'error' красная
    echo $OUTPUT->notification("Действие выполненно", 'success');
}
else
{
    //получили строку для дальнейшего запроса
    //$xmlData = GetXML($res, $creator, $cipherProg);
   
    echo $OUTPUT->notification('Получили результаты из БД, формируем xml-строку...', 'success');
    //Получили ответ от сервера 
    //$xmlOut  = ConnectMSSql($xmlData, $courseid);
}

//освободил переменную
unset($res);

echo $OUTPUT->continue_button("$CFG->wwwroot/report/report_pstgu_deanery/index.php?id=$courseid");
echo $OUTPUT->footer();
