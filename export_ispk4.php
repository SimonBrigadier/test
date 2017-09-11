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


function export_ispk4($course, $context)
{
    global $OUTPUT, $CFG, $PAGE, $DB, $USER;
    $roleid       = 5; // здесь нам нужны только студенты
    $courseid     = $course->id;

    $cohortselection    = optional_param('cohortselection', '0', PARAM_RAW); // When rendering checkboxes against users mark them all checked.

    $PAGE->set_url
    (
        '/report/report_pstgu_deanery/index.php', 
        array
        (   
            'id' => $courseid,
            'page' => 4
        )
    );

    //запрашиваем необходимые поля только для роли студента

    $profile_roles = GetProfileRoles($roleid);

    $rolenames = role_fix_names($profile_roles, $context, ROLENAME_ALIAS, true);

    // Make sure other roles may not be selected by any means.
    if (empty($rolenames[$roleid])) {
        print_error('noparticipants');
    }

    // No roles to display yet?
    // frontpage course is an exception, on the front page course we should display all users.
    if (empty($rolenames) && !$isfrontpage) {
        if (has_capability('moodle/role:assign', $context)) {
            redirect($CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$context->id);
        } else {
            print_error('noparticipants');
        }
    }

    // Trigger events.
    //user_list_view($course, $context);
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

    // Оперции на получение пользователей, оценок и дат заврешений, названий глобальных групп
    $params = array();
    //получаем параметры контекста для данной роли на данном курсе
    $params = array_merge($params, $context->get_parent_context_ids(true));
    $userlist = get_course_enroll_users($course->id, $roleid, $params[0]);

    if(count($userlist) == 0)
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
    $infocell = new html_table_cell( html_writer::label(get_string('descriptionactionstab4', 'report_report_pstgu_deanery'), 'participantsform'));
    $infocell->attributes['class'] = 'left';
    //инфо для подробного описания действий
    $helpiconcell = new html_table_cell($OUTPUT->help_icon('helpinfotab4', 'report_report_pstgu_deanery'));
    $helpiconcell->attributes['class'] = 'left';

    $infotable = new html_table();
    $infotable->attributes['class'] = 'controls';
    //добавляем строки и ячейки
    $infotable->data[] = new html_table_row();
    $infotable->data[0]->cells[] = $infocell;
    $infotable->data[0]->cells[] = $helpiconcell;
    $infotable->data[0]->cells[] = $emptycell;

    //1 
    $cohortselectionlabelcell = new html_table_cell(html_writer::label('1. Выберите учебную группу СДО', 'cohortselection'));
    $cohortselectionlabelcell->attributes['class'] = 'left';
    $options = get_users_cohorts(array_keys($userlist));
    $attr = array('label' => '');
    if(count($options) == 1)
        $attr['disabled'] = 'disabled';
    //$cohortselectionForm =  '<div class="cohortselectionform">';
    $cohortselectionFormURL = new moodle_url('/report/report_pstgu_deanery/index.php', array('id' => $course->id, 'page' => 4));
    //$cohortselectionForm .= $OUTPUT->single_select($cohortselectionFormURL, 'cohortselection',$options, $cohortselection, null, 'cohortselectionform', $attr);
    //$cohortselectionForm .= '</div>';
    $cohortselectioncell = new html_table_cell(html_writer::select($options, 'cohortselection', $cohortselection, null, $attr));
    $cohortselectioncell->attributes['class'] = 'left';

    //справка по выбору из выпадающего меню
    $cohortselectioninfocell = new html_table_cell($OUTPUT->help_icon('cohortselectioninfo', 'report_report_pstgu_deanery'));
    $cohortselectioninfocell->attributes['class'] = 'left';
    $cohortselectiontable = new html_table();
    $cohortselectiontable->attributes['class'] = 'controls';
    //$cohortselectiontable->width = '300';
    $cohortselectiontable->data[] = new html_table_row();
    $cohortselectiontable->data[0]->cells[] = $cohortselectionlabelcell;
    $cohortselectiontable->data[0]->cells[] = $cohortselectioncell;
    $cohortselectiontable->data[0]->cells[] = $cohortselectioninfocell;
    $cohortselectiontable->data[0]->cells[] = $emptycell;

    $group = get_users_cohorts(array_keys($userlist), true);
    if($cohortselection == '0')
    {
        $keys = array_keys($group);
        $cohortselection = $keys[0];
    }
    $val = get_group_key($group[$cohortselection]->idnumber);
    $cohortrelationlabelcell = new html_table_cell(html_writer::label('Связана с группой в ИС Деканат', 'cohortrelation'));
    $cohortrelationlabelcell->attributes['class'] = 'left';
    $cohortrelationtextcell = new html_table_cell('<input type="text" name="cohortrelation" value="'.$val[1].'" disabled="disabled" />');
    $cohortrelationtextcell->attributes['class'] = 'left';
    $cohortrelationtable = new html_table();
    $cohortrelationtable->attributes['class'] = 'controls';
    //$cohortrelationtable->width = '500';
    $cohortrelationtable->data[] = new html_table_row();
    $cohortrelationtable->data[0]->cells[] = $cohortrelationlabelcell;
    $cohortrelationtable->data[0]->cells[] = $cohortrelationtextcell;
    $cohortrelationtable->data[0]->cells[] = $emptycell;

    //   2. Нажмите кнопку
    $cohortverifylabelcell = new html_table_cell(html_writer::label('2. Нажмите кнопку', 'verify'));
    $cohortverifylabelcell->attributes['class'] = 'left';
    $cohortverifybuttoncell = new html_table_cell('<input type="submit" id="id_submitbutton" name="verify" formaction="'.$cohortselectionFormURL.'" value="Сверить состав группы"/>');
    $cohortverifybuttoncell->attributes['class'] = 'left';
    $cohortverifytable = new html_table();
    $cohortverifytable->attributes['class'] = 'controls';
    $cohortverifytable->data[] = new html_table_row();
    $cohortverifytable->data[0]->cells[] = $cohortverifylabelcell;
    $cohortverifytable->data[0]->cells[] = $cohortverifybuttoncell;
    $cohortverifytable->data[0]->cells[] = $emptycell;


    //3. Проверьте результаты сверки
    //надпись с описанием действий
    $resultverifyinfocell = new html_table_cell( html_writer::label('3. Проверьте результаты сверки', 'participantsform'));
    $resultverifyinfocell->attributes['class'] = 'left';
    //инфо для подробного описания действий
    $resultverifyhelpiconcell = new html_table_cell($OUTPUT->help_icon('resultverifyinfo', 'report_report_pstgu_deanery'));
    $resultverifyhelpiconcell->attributes['class'] = 'left';
    $resultverifytable = new html_table();
    $resultverifytable->attributes['class'] = 'controls';
    $resultverifytable->width = '300';
    $resultverifytable->data[] = new html_table_row();
    $resultverifytable->data[0]->cells[] = $resultverifyinfocell;
    $resultverifytable->data[0]->cells[] = $resultverifyhelpiconcell;
    $resultverifytable->data[0]->cells[] = $emptycell;

    echo '<form action="action_redir.php" method="post" id="participantsform" >';
    echo '<div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';
    //отрисовка таблиц
    echo html_writer::table($infotable);
    echo html_writer::table($cohortselectiontable);
    echo html_writer::table($cohortrelationtable);
    echo html_writer::table($cohortverifytable);
    echo html_writer::table($resultverifytable);


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
            if(strpos($ans, 'OK') !== FALSE)
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
    echo '<div class="userlist">';

    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = new moodle_url
                (
                    '/report/report_pstgu_deanery/index.php', 
                    array
                    (
                        'roleid' => $roleid,
                        'id' => $course->id,
                        'page' => 4
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

    //фото
    $tablecolumns[] = 'userpic';
    //ФИО в СДО
    $tablecolumns[] = 'lastname1';
    //ФИО в ИС Деканат
    $tablecolumns[] = 'lastname2';
    //id в ИС Деканат
    $tablecolumns[] = 'idisd';
    //задаем название столбцов
    $tableheaders[] = 'Фото'; //get_string('userpic');
    $tableheaders[] = 'ФИО в СДО';
    $tableheaders[] = 'ФИО в ИС Деканат';
    $tableheaders[] = 'id в ИС Деканат';


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
            // 1 добавили картинку 
            $data[] = $OUTPUT->user_picture($user, array('size' => 35, 'courseid' => $course->id));
            // 2 ФИО в СДО
            $data[] = $profilelink;                  
            // 3 ФИО в ИС
            $idisd = idisd_by_name($user, $user_from_is_dean);
            if($idisd === false)
            {
                $data[] = 'не найден';
                $data[] = '-';
            }            
            else
            {
                $data[] = $user_from_is_dean[$idisd]->Фамилия .' '. $user_from_is_dean[$idisd]->Имя .' '.$user_from_is_dean[$idisd]->Отчество;
                $data[] = $idisd;
            }
            //добавили строку в таблицу
            $table->add_data($data);
        }
        foreach($user_from_is_dean as $user)
        {
            if(idisd_by_name($user, $userlist) === false)
            {
                //масcив ячеек для строки
                $data = array();            
                $data[] = $OUTPUT->user_picture((object) array('id' => 1, 'firstname' => 'firstname', 'lastname'=>'lastname' ), array('size' => 35, 'courseid' => $course->id));;
                $data[] = 'не найден';
                $data[] = $user->Фамилия .' '. $user->Имя .' '.$user->Отчество;
                $data[] = $user->Код;
                $table->add_data($data);
            }
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
    echo '</div></form>';     

    echo '</div>';  // Userlist.

    if ($userlist) 
    {
        unset($userlist);
    }
    if($table)
    {
        unset($table);
    }

}

