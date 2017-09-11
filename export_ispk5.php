
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
 * Lists all the users within a given course.
 *
 * @copyright 2017 Simon Brigadier
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

function export_ispk5($course, $context)
{
    global $OUTPUT, $CFG, $PAGE, $DB,$USER;
    require_once $CFG->dirroot . '/report/report_pstgu_deanery/grade_date_form.php';

    // OUTPUT API можно найти в D:\Bitnami\apps\moodleBM\htdocs\lib\outputrenderers.php
    // методы html_writer можно посмотреть D:\Bitnami\apps\moodleBM\htdocs\lib\outputcomponents.php
    // вывод селектора даты
    //echo html_writer::select_time('days', 'gradeday', time());
    //echo html_writer::select_time('months', 'grademonths', time());
    //echo html_writer::select_time('years', 'gradeyears', time());
    //значек календаря, только сам календарь не появляется ;-)
    //$image = $OUTPUT->pix_icon('i/calendar', get_string('calendar', 'calendar'), 'moodle');
    //echo '&nbsp;'. html_writer::link('#', $image, array('name' => 'x[calendar]'));

    $roleid       = 5; // здесь нам нужны только студенты
    $courseid     = $course->id; // This are required.
    
    $PAGE->set_url('/report/report_pstgu_deanery/index.php', array('id' => $courseid, 'page' => 5));
    
    $isfrontpage = ($course->id == SITEID);
    //запрашиваем необходимые поля только для роли студента
    $profile_roles = GetProfileRoles($roleid);

    $rolenames = role_fix_names($profile_roles, $context, ROLENAME_ALIAS, true);

    // Make sure other roles may not be selected by any means.
    if (empty($rolenames[$roleid])) {
        print_error('noparticipants');
    }
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
    // получаем шифр программы (краткое название курса)
    //устанавливаем языковые настройки локали для парсинга
    setlocale(LC_ALL, 'ru_RU.utf8');
    //разделили на массив из строк
    $arr = preg_split('/[^\w]+/u', $course->shortname);
    //Защита от вывода ошибочного шифра программы
    if(count($arr) < 2)
    {
        echo $OUTPUT->notification(get_string('errornotcipher', 'report_report_pstgu_deanery'),'error');
        echo $OUTPUT->footer();
        exit;
    }

    $course_ciph = extract_course_cipher($arr);

    $options = GetCohorts($course_ciph->cipher);
    if(count($options) == 0)
    {
        echo $OUTPUT->notification("В СДО нет групп с шифром $course_ciph->cipher",'error');
        echo $OUTPUT->footer();
        exit;
    }

    $g = array_keys($options);
    $cohortselection = optional_param('cohortselection', $g[0], PARAM_RAW);

    // Оперции на получение пользователей, оценок и дат заврешений, названий глобальных групп
    $params = array();
    //получаем параметры контекста для данной роли на данном курсе
    $params = array_merge($params, $context->get_parent_context_ids(true));

    //получили ид итоговой оценки
    $TotalGrade = GetTotalGradeId($course);
    if(!isset($TotalGrade->id))
    {
        $TotalGrade = new stdClass();
        $TotalGrade->id = 0;
    }

    $CheckedUsers = get_users_by_cohort($courseid, $roleid, $params[0], $TotalGrade->id, $cohortselection);

    $UnCheckedUsers = array();
    $UnCheckedUsers = MergeUserList(array_keys($CheckedUsers), $TotalGrade->id, $course->id, $roleid, $params[0], $course_ciph->cipher);

    if(count($CheckedUsers) == 0 && count($UnCheckedUsers) == 0)
    {
        //echo $OUTPUT->heading('');
        echo $OUTPUT->notification('На данном курсе нет зачисленных студентов.','error');
        echo $OUTPUT->footer();
        exit;
    }
    // ячейка пустая, чтобы сохранить выранивание слева
    $emptycell = new html_table_cell('&nbsp;');
    $emptycell->attributes['class'] = 'left';
    //надпись с описанием действий
    $infocell = new html_table_cell( html_writer::label('<b>'. get_string('tab5info', 'report_report_pstgu_deanery').'</b>', 'participantsform'));
    $infocell->attributes['class'] = 'left';
    //инфо для подробного описания действий
    $helpiconcell = new html_table_cell($OUTPUT->help_icon('tabinfodesc', 'report_report_pstgu_deanery'));
    $helpiconcell->attributes['class'] = 'left';

    $infotable = new html_table();
    $infotable->attributes['class'] = 'controls';
    //добавляем строки и ячейки
    $infotable->data[] = new html_table_row();
    $infotable->data[0]->cells[] = $infocell;
    $infotable->data[0]->cells[] = $helpiconcell;
    $infotable->data[0]->cells[] = $emptycell;

    //1 надпись
    $labelcell = new html_table_cell(html_writer::label(get_string('1tab5', 'report_report_pstgu_deanery'), 'cohortselection'));
    $labelcell->attributes['class'] = 'left';

    $table1 = new html_table();
    $table1->attributes['class'] = 'controls';
    $table1->data[] = new html_table_row();
    $table1->data[0]->cells[] = $labelcell;
    $table1->data[0]->cells[] = $emptycell;

    //надпись для меню с группами
    $cohortselectionlabelcell = new html_table_cell(html_writer::label('Учебная группа в СДО&nbsp;', 'cohortselection'));
    $cohortselectionlabelcell->attributes['class'] = 'left';
    //меню с группами
    $attr = array('label' => '');
    if(count($options) == 1)
        $attr['disabled'] = 'disabled';
    $cohortselectionForm =  '<div class="cohortselectionform">';
    $cohortselectionFormURL = new moodle_url('/report/report_pstgu_deanery/index.php', array('id' => $course->id,'page' => 5));
    $cohortselectionForm .= $OUTPUT->single_select($cohortselectionFormURL, 'cohortselection',$options, $cohortselection, null, 'cohortselectionform', $attr);
    $cohortselectionForm .= '</div>';
    //html_writer::select($options, 'cohortselection', $cohortselection, null, $attr)
    $cohortselectioncell = new html_table_cell($cohortselectionForm);
    $cohortselectioncell->attributes['class'] = 'left';

    $cohortmenutable = new html_table();
    $cohortmenutable->attributes['class'] = 'controls';
    $cohortmenutable->data[] = new html_table_row();
    $cohortmenutable->data[0]->cells[] = $cohortselectionlabelcell;
    $cohortmenutable->data[0]->cells[] = $cohortselectioncell;
    $cohortmenutable->data[0]->cells[] = $emptycell;

    //надпись для поля Группа в ИС "Деканат"
    $cohortrelationlabelcell = new html_table_cell(html_writer::label('Группа в ИС "Деканат"&nbsp;', 'cohortrelation'));
    $cohortrelationlabelcell->attributes['class'] = 'left';
    // текстовоее поле
    $groupidnumber = $DB->get_record_sql('SELECT idnumber FROM {cohort} WHERE id = ?', array($cohortselection));
    $cohortrelationtextcell = new html_table_cell('<input type="text" name="cohortrelation" value="'.$groupidnumber->idnumber.'" disabled="disabled" />');
    $cohortrelationtextcell->attributes['class'] = 'left';
    //справка по выбору из выпадающего меню
    $cohortselectioninfocell = new html_table_cell($OUTPUT->help_icon('cohortselectioninfo', 'report_report_pstgu_deanery'));
    $cohortselectioninfocell->attributes['class'] = 'left';

    $cohortrelationtable = new html_table();
    $cohortrelationtable->attributes['class'] = 'controls';
    $cohortrelationtable->data[] = new html_table_row();
    $cohortrelationtable->data[0]->cells[] = $cohortrelationlabelcell;
    $cohortrelationtable->data[0]->cells[] = $cohortrelationtextcell;
    $cohortrelationtable->data[0]->cells[] = $cohortselectioninfocell;
    $cohortrelationtable->data[0]->cells[] = $emptycell;

    //надпись для поля Дисциплина
    $subjlabelcell = new html_table_cell(html_writer::label('Дисциплина&nbsp;', 'cohortrelation'));
    $subjlabelcell->attributes['class'] = 'left';
    // текстовоее для поля Дисциплина
    $subjtextcell = new html_table_cell('<input type="text" name="subj" value="'.$course->fullname.'" disabled="disabled" />');
    $subjtextcell->attributes['class'] = 'left';
    //справка для поля Дисциплина
    $subjinfocell = new html_table_cell($OUTPUT->help_icon('subjinfo', 'report_report_pstgu_deanery'));
    $subjinfocell->attributes['class'] = 'left';
    //таблица для поля Дисциплина
    $subjtable = new html_table();
    $subjtable->attributes['class'] = 'controls';
    $subjtable->data[] = new html_table_row();
    $subjtable->data[0]->cells[] = $subjlabelcell;
    $subjtable->data[0]->cells[] = $subjtextcell;
    $subjtable->data[0]->cells[] = $subjinfocell;
    $subjtable->data[0]->cells[] = $emptycell;
    //   надпись для поля Ведомость №
    $spreadsheetlabelcell = new html_table_cell(html_writer::label('Ведомость №&nbsp;', 'spreadsheet'));
    $spreadsheetlabelcell->attributes['class'] = 'left';
    // текстовое поле для поля Ведомость №
    $spreadsheettextcell = new html_table_cell('<input type="text" name="spreadsheet" value="'.$course->fullname.'" disabled="disabled" />');
    $spreadsheettextcell->attributes['class'] = 'left';
    //справка для поля Ведомость №
    $spreadsheetinfocell = new html_table_cell($OUTPUT->help_icon('spreadsheet', 'report_report_pstgu_deanery'));
    $spreadsheetinfocell->attributes['class'] = 'left';
    // таблица для поля Ведомость №
    $spreadsheettable = new html_table();
    $spreadsheettable->attributes['class'] = 'controls';
    $spreadsheettable->data[] = new html_table_row();
    $spreadsheettable->data[0]->cells[] = $spreadsheetlabelcell;
    $spreadsheettable->data[0]->cells[] = $spreadsheettextcell;
    $spreadsheettable->data[0]->cells[] = $spreadsheetinfocell;
    $spreadsheettable->data[0]->cells[] = $emptycell;

    //   надпись  Тип ведомости
    $spreadsheettypelabelcell = new html_table_cell(html_writer::label('Тип ведомости&nbsp;', 'spreadsheettype'));
    $spreadsheettypelabelcell->attributes['class'] = 'left';
    // текстовое поле для  Тип ведомости
    $spreadsheettypetextcell = new html_table_cell('<input type="text" name="spreadsheettype" value="'.'экзамен'.'" disabled="disabled" />');
    $spreadsheettypetextcell->attributes['class'] = 'left';
    //справка для поля Ведомость №
    $spreadsheettypeinfocell = new html_table_cell($OUTPUT->help_icon('spreadsheettype', 'report_report_pstgu_deanery'));
    $spreadsheettypeinfocell->attributes['class'] = 'left';
    // таблица для  Тип ведомости
    $spreadsheettypetable = new html_table();
    $spreadsheettypetable->attributes['class'] = 'controls';
    $spreadsheettypetable->data[] = new html_table_row();
    $spreadsheettypetable->data[0]->cells[] = $spreadsheettypelabelcell;
    $spreadsheettypetable->data[0]->cells[] = $spreadsheettypetextcell;
    $spreadsheettypetable->data[0]->cells[] = $spreadsheettypeinfocell;
    $spreadsheettypetable->data[0]->cells[] = $emptycell;

    //2. Укажите даты оценок
    //надпись с описанием действий
    $gradedatelabelcell = new html_table_cell( html_writer::label(get_string('2tab5', 'report_report_pstgu_deanery'), 'participantsform'));
    $gradedatelabelcell->attributes['class'] = 'left';
    $gradedateselectcell = new html_table_cell(html_writer::select(array('даты оценок за курс', 'указать вручную'), 'gradedateselect', 0, null));
    $gradedateselectcell->attributes['class'] = 'left';
    //форма с селектором даты и календарем
    $date_form = new grade_date_form();
    //$data = $editform->get_data() получили данне с датой
    $gradedateformcell = new html_table_cell($date_form->render()); 
    $gradedateformcell->attributes['class'] = 'left';
    $gradedatehelpcell = new html_table_cell($OUTPUT->help_icon('gradedate','report_report_pstgu_deanery'));
    $gradedatehelpcell->attributes['class'] = 'left';

    $gradedatetable = new html_table();
    $gradedatetable->attributes['class'] = 'controls';
    //$gradedatetable->width = '300';
    $gradedatetable->data[] = new html_table_row();
    $gradedatetable->data[0]->cells[] = $gradedatelabelcell;
    $gradedatetable->data[0]->cells[] = $gradedateselectcell;
    $gradedatetable->data[0]->cells[] = $gradedateformcell;
    $gradedatetable->data[0]->cells[] = $gradedatehelpcell;
    $gradedatetable->data[0]->cells[] = $emptycell;
    
    //3.Выберите студентов, для которых необходимо выгрузить оценки.
    $selectstudslabelcell = new html_table_cell( html_writer::label(get_string('3tab5', 'report_report_pstgu_deanery'), 'participantsform'));
    $selectstudslabelcell->attributes['class'] = 'left';
    $selectstudstable  = new html_table();
    $selectstudstable->attributes['class'] = 'controls';
    $selectstudstable->data[] = new html_table_row();
    $selectstudstable->data[0]->cells[] = $selectstudslabelcell;
    $selectstudstable->data[0]->cells[] = $emptycell;
    
    //4. Нажмите кнопку
    $downloadgradelabelcell = new html_table_cell( html_writer::label(get_string('4tab5', 'report_report_pstgu_deanery'), 'participantsform'));
    $downloadgradelabelcell->attributes['class'] = 'left';
    $btnurl = $PAGE->url;
    $btn = html_writer::tag('input', '', array('type' => 'submit','id'=>'id_submitbutton','name'=>'downloadgrade','formaction'=>$btnurl,'value'=>'Выгрузить оценки за курс'));
    //debug_in_file($btn,'btn.php');
    $downloadgradebtncell = new html_table_cell($btn);
    $downloadgradebtncell->attributes['class'] = 'left';
    $downloadgradetable  = new html_table();
    $downloadgradetable->attributes['class'] = 'controls';
    $downloadgradetable->data[] = new html_table_row();
    $downloadgradetable->data[0]->cells[] = $downloadgradelabelcell;
    $downloadgradetable->data[0]->cells[] = $downloadgradebtncell;
    $downloadgradetable->data[0]->cells[] = $emptycell;
    // 5.Проверьте результат выгрузки оценок.
    $checkresdownloadslabelcell = new html_table_cell(html_writer::label(get_string('5tab5', 'report_report_pstgu_deanery'), 'participantsform')); 
    $checkresdownloadslabelcell->attributes['class'] = 'left';
    $checkresdownloadshelpcell = new html_table_cell($OUTPUT->help_icon('verifyres','report_report_pstgu_deanery'));
    $checkresdownloadshelpcell->attributes['class'] = 'left';
    $checkresdownloadtable = new html_table();
    $checkresdownloadtable->attributes['class'] = 'controls';
    $checkresdownloadtable->data[] = new html_table_row();
    $checkresdownloadtable->data[0]->cells[] = $checkresdownloadslabelcell;
    $checkresdownloadtable->data[0]->cells[] = $checkresdownloadshelpcell;
    $checkresdownloadtable->data[0]->cells[] = $emptycell;
    echo '<form action="action_redir.php" method="post" id="participantsform" >';
    echo '<div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';
    //отрисовка таблиц
    echo html_writer::table($infotable);
    echo html_writer::table($table1);
    echo html_writer::table($cohortmenutable);
    echo html_writer::table($cohortrelationtable);
    echo html_writer::table($subjtable);
    echo html_writer::table($spreadsheettable);
    echo html_writer::table($spreadsheettypetable);
    echo html_writer::table($gradedatetable);
    echo html_writer::table($selectstudstable);
    echo html_writer::table($downloadgradetable);
    echo html_writer::table($checkresdownloadtable);
    $formaction = new moodle_url('/report/report_pstgu_deanery/index.php',array('page' => 5));
    echo '<input name="selectall" type="submit" for="participantsform" formaction="'.$formaction.'" value="Выбрать всех" /> ';
    echo '<input type="submit" name="selectcomplcourse" id="selectcomplstuds" formaction="'.$formaction.'" value="Выбрать завершивших курс"/>';
    echo '<input name="disselect" type="submit" for="participantsform" formaction="'.$formaction.'" value="Очистить выбор" /> ';
    echo '</div></form>'; 

    //page_develop_sorry_message();
    //data processing
    $info = array();

    $user_from_is_dean = array();

    // получили данные с предыдущей отправки формы для выделения пользователей
    if ($formdata = data_submitted() and confirm_sesskey()) 
    {   
        if(isset($formdata->verify))
        {
            // выполнение выбранных действий
            $ans = get_user_relations($user_from_is_dean, $val[0]);
            if(strpos($ans, 'ODBC') !== false)
            {
                echo $OUTPUT->notification('Действие не выполнено! Пожалуйста, сделайте снимок экрана с этой ошибкой и отправьте его в техподдержку.<br>'.$ans,'error');
            }
            elseif(strpos($ans, 'OK') !== FALSE)
            {
                if(!set_user_relations($user_from_is_dean, $userlist, $cohortselection))
                        echo $OUTPUT->notification(get_string('idisdnotfound', 'report_report_pstgu_deanery'),'info');
                else
                    echo $OUTPUT->notification("Действие выполнено",'success');

                $userlist = get_course_enroll_users($course->id, $roleid, $params[0]);
            }
            else
            {
                echo $OUTPUT->notification('Действие не выполнено!<br>'.$ans,'error');
            }
        }   

    }
    $userlist = get_course_enroll_users($course->id, $roleid, $params[0]);
    echo '<div class="userlist">';

    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = new moodle_url
                (
                    '/report/report_pstgu_deanery/index.php', 
                    array
                    (   'roleid' => $roleid,
                        'id' => $course->id, 
                        'page' => 5
                    )
                );

    // Setting up tags.
    if ($course->id == SITEID) {
        $filtertype = 'site';
    } else if ($course->id && !$currentgroup) {
        $filtertype = 'course';
        $filterselect = $course->id;
    } else {
        $filtertype = 'group';
        $filterselect = $currentgroup;
    }

    // Таблица с элементами управления Выводится в верхней части таблицы с юзерами  
    // Print settings and things in a table across the top.
    // ячейка пустая, чтобы сохранить выранивание слева
    // Define a table showing a list of users in the current role selection.
    $tablecolumns = array();
    $tableheaders = array();
    //обозначаем столбцы

    //обозначаем столбцы
    //галочка
    if ($bulkoperations) 
    {
        $tablecolumns[] = 'select';
        $tableheaders[] = get_string('select');
    }
    //фото
    $tablecolumns[] = 'userpic';
    //ФИО
    $tablecolumns[] = 'lastname';
    //дата оценки
    $tablecolumns[] = 'gradedate';
    //оценка
    $tablecolumns[] = 'grade';
    //результат выгрузки
    $tablecolumns[] = 'downloadresult';
    //задаем название столбцов
    $tableheaders[] = 'Фото'; //get_string('userpic');
    $tableheaders[] = 'ФИО';
    $tableheaders[] = 'Дата оценки за курс в СДО';
    $tableheaders[] = 'Оценка за курс в СДО';
    $tableheaders[] = 'Результат выгрузки';


    $table = new flexible_table('user-index-participants-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());
    //устанавливаем сортировку по фамилии, столбец сортировки по умолчинию и направление сортировки
    //$table->sortable(true, 'lastname', SORT_ASC);

    ////задаем несортабельные столбцы
    //$table->no_sorting('lastname');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'participants');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'right');
    //$table->column_style('serialnumber', 'align', 'right');

    $table->set_control_variables(array(
                TABLE_VAR_SORT    => 'ssort',
                TABLE_VAR_HIDE    => 'shide',
                TABLE_VAR_SHOW    => 'sshow',
                TABLE_VAR_IFIRST  => 'sifirst',
                TABLE_VAR_ILAST   => 'silast',
                TABLE_VAR_PAGE    => 'spage'
                ));
    $table->setup();

    $table->initialbars(true);

    //заливаем список пользователей в таблицу
    //таблица 
    if ($userlist) 
    {
        //распечатываем таблицу со студентами
        $usersprinted = array();
        foreach ($userlist as $user) 
        {
            if (in_array($user->id, $usersprinted)) 
            { // Prevent duplicates by r.hidden - MDL-13935.
              // пропускаем дубликатов
                continue;
            }
            //запоминаем, кого нвывели на страницу
            $usersprinted[] = $user->id; // Add new user to the array of users printed.        

            context_helper::preload_from_record($user);

            $usercontext = context_user::instance($user->id);
            //в зависимости от прав выводим имя пользователя в виде ссылки
            if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) 
            {
                $profilelink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.$user->lastname.' '.$user->firstname.'</a>';
            } else 
            {
                $profilelink = $user->lastname.' '.$user->firstname;
            }

            //масcив ячеек для строки
            $data = array();  
            $is_last_check = FALSE;
            //галочка
            if($formdata && isset($formdata->users))
            {
                $is_last_check = array_key_exists($user->id, $formdata->users);
            }        

            if ($bulkoperations) 
            {
                if ($selectall || (array_key_exists($user->id, $CheckedUsers) || $is_last_check)) 
                {
                    $checked = 'checked="checked"  ';//value="1"
                } 
                else 
                {
                    $checked = 'value="1" ';
                }
                // 1 добавили чекбокс для выбора пользователя
                $data[] = '<input class="usercheckbox" name="users['.$user->id.']" type="checkbox" ' . $checked .'/>';
            }
            // 2 добавили картинку 
            $data[] = $OUTPUT->user_picture($user, array('size' => 35, 'courseid' => $course->id));
            // 3 ФИО в СДО
            $data[] = $profilelink;
            // 4 Дата оценки за курс сдо
            $data[] = 'Дата оценка';
            // 5 Оценки
            $data[] = 'Оценка';
            // 6 Результат выгрузки
            $data[] = 'найден';
            //добавили строку в таблицу
            $table->add_data($data);
        }
        //освобождаем ресурсоемкие переменные
        unset($usersprinted);
    }
    //вывод таблицы
    $table->print_html();

    //кнопки    
    echo '<noscript style="display:inline">';
    echo '<input type="submit" value="'.get_string('ok').'" />';
    echo '</noscript>';    
    echo '<input type="hidden" name="id" value="'.$course->id.'" />'; 
    echo '</div></form>';    // Userlist.
    echo '</div>';  //form

    if ($userlist) 
    {
        unset($userlist);
    }
    if($table)
    {
        unset($table);
    }
}