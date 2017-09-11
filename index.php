<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This page is provided for compatability and redirects the user to the default grade report
 *
 * @package   core_grades
 * @copyright 2005 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/filelib.php');
require_once $CFG->dirroot . '/report/report_pstgu_deanery/lib.php';
require_once $CFG->dirroot . '/report/report_pstgu_deanery/text_area_form.php';

$courseid     = optional_param('id', 0, PARAM_INT); // This are required.
$tmppage      = optional_param('page', 1, PARAM_INT); // This are required.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
// Check permissions
require_capability('report/report_pstgu_deanery:view', $context);
//bulk operations - массовые операции
$bulkoperations = has_capability('moodle/course:bulkmessaging', $context);
// Check to see if groups are being used in this course
// and if so, set $currentgroup to reflect the current group.
$groupmode    = groups_get_course_groupmode($course);   // Groups are being used.
$currentgroup = groups_get_course_group($course, true);

if (!$currentgroup) {      // To make some other functions work better later.
    $currentgroup  = null;
}



$isseparategroups = ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context));
$url = new moodle_url('/report/report_pstgu_deanery/index.php', array('id' => $courseid,'page' => $tmppage));
//заголовок страницы
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(format_string("$course->shortname: ".get_string('pluginname', 'report_report_pstgu_deanery')));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add('Отчеты');//добавили в панель навигации узел без ссылки
$PAGE->navbar->add(get_string('pluginname', 'report_report_pstgu_deanery'), $url);// узел со ссылкой
echo $OUTPUT->header();
//берем настройки по вкладкам
$tab = $DB->get_records_sql("SELECT name, value FROM {config} WHERE name LIKE 'export_ispk%'");
if($tab["export_ispk$tmppage"]->value == '0')
{
    echo $OUTPUT->notification('Данная вкладка не доступна!','error');
    echo $OUTPUT->footer();
    exit;
}
print_deanery_tabs($tmppage, $courseid, $context);
//$l = $PAGE->navbar->get_items();
//$str = '';
//foreach($l as  $value)
//{
//    $str .= $value->parent->text.'->';
//}

//debug_message( 'панель навигации ' . $str );
// Отображение страницы
require_once $CFG->dirroot . "/report/report_pstgu_deanery/export_ispk$tmppage.php";
$function_name = "export_ispk$tmppage";
if(function_exists ( $function_name ))
    $function_name($course, $context);
    

$module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
$PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
// Запросили JS из файла
//$PAGE->requires->js('/report/report_pstgu_deanery/report_pstgu_deanery_js.js');
// Добавли вызов функции, которую описали в наше JS файле
// Переделаи null в качестве аргументов
// true для того, чтобы фукнция вызывалась только после того как объектная модель страницы (DOM)
// 1 задержка до вызова функции
//$PAGE->requires->js_function_call('report_pstgu_deanery_disablid_if', null, true, 1);
//$PAGE->requires->js_function_call('report_pstgu_deanery_submit_dialog', null, true, 1);


echo $OUTPUT->footer();