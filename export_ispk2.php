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

function export_ispk2($course, $context)
{
    global $OUTPUT, $USER, $CFG, $PAGE, $DB;
    //$page         = optional_param('page', 0, PARAM_INT); // Which page to show.
    $checkcount   = optional_param('checkcount', 0, PARAM_INT); // How many per page. количество для выбора первых сколько-то
    $roleid       = 5; // здесь нам нужны только студенты
    $contextid    = optional_param('contextid', 0, PARAM_INT); // One of this or.
    $courseid     = optional_param('id', 0, PARAM_INT); // This are required.
    $selectall    = optional_param('selectall', false, PARAM_BOOL); // When rendering checkboxes against users mark them all checked.
    $selectA      = optional_param('selectA', 0, PARAM_INT);//пункт меню для выбора первых сколь-то
    $actselect    = optional_param('actselect', 0, PARAM_INT);
    $sort         = optional_param('sort', '0', PARAM_RAW);//признак сортировки для флажка
    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    $execaction = optional_param('execaction', null, PARAM_RAW);//признак сортировки для флажка


    $PAGE->set_url('/report/report_pstgu_deanery/index.php', array(        
            'checkcount' => $checkcount,
            'actselect' => $actselect,
            'contextid' => $contextid,
            'id' => $courseid,
            'selectA' => $selectA,
            'sort' => $sort,
            'page' => 2));

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

    $info = array();

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
    $CheckedUsers = array();
    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = new moodle_url('/report/report_pstgu_deanery/index.php', array(        
            'checkcount' => $checkcount,
            'actselect' => $actselect,
            'selectA' => $selectA,
            'sort' => $sort,
            'page' => 2));
    //открываем тег формы, чтобы отрпавлялись пользователи с окна подтверждения
    echo '<form action="'.$baseurl.'" method="post" id="participantsform" >';
    // получили данные с предыдущей отправки формы для выделения пользователей
    if ($formdata = data_submitted() and confirm_sesskey()) 
    {   
        $errors = '';
        if(isset($formdata->execaction) || isset($execaction))
        {
            if($actselect == '2' && !$confirm)
            {
                $message = 'Вы действиетельно хотите исключить выбранных пользователей из учебной группы?';

                echo $OUTPUT->heading('Исключение из группы');

                $confirmform = '';          

                $continue = new moodle_url("$CFG->wwwroot/report/report_pstgu_deanery/index.php", array(        
                                                'checkcount' => $checkcount,
                                                'contextid' => $contextid,
                                                'actselect' => $actselect,
                                                'selectA' => $selectA,
                                                'id' => $course->id,
                                                'sort' => $sort,
                                                'confirm'=>1,
                                                'execaction'=>'exec',
                                                'page' => 2));            
                //$formcontinue = new single_button($continueurl, get_string('yes'));

                $return = new moodle_url("$CFG->wwwroot/report/report_pstgu_deanery/index.php", array(        
                                                'checkcount' => $checkcount,
                                                'actselect' => '0',                
                                                'selectA' => $selectA,
                                                'contextid' => $contextid,
                                                'id' => $course->id,
                                                'sort' => $sort,
                                                'page' => 2));
                //заголовок                
                $confirmform .= html_writer::tag('h4', get_string('confirm'));

                //сообщение
                $confirmform .= html_writer::tag('p', $message);

                //кнопки
                $confirmform .= '<input type="submit" formaction="'.$continue->out(). '" value="'.get_string('yes').'"/>';
                $confirmform .= '<input type="submit" formaction="'.$return->out(). '" value="'.get_string('no').'"/>';
                $confirmform .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                $confirmform .= '<input type="hidden" name="cohortmenu" value="'.$formdata->cohortmenu.'" />';
                $confirmform .= '<input type="hidden" name="id" value="'.$formdata->id.'" />';
                if(isset($formdata->users))
                {
                   foreach($formdata->users as $k=>$v)
                       $confirmform .= '<input type="hidden" name="users['.$k.']"/>';
                }

                $confirmcell = new html_table_cell($confirmform);
                $confirmcell->attributes['class'] = 'left';

                $confirmtable = new html_table();
                $confirmtable->attributes['class'] = 'controls';
                $confirmtable->data[] = new html_table_row();
                $confirmtable->data[0]->cells[] = $confirmcell;
                echo html_writer::table($confirmtable);
                echo html_writer::end_tag('form');             
                echo $OUTPUT->footer();            
                exit;
            }
            if(isset($formdata->users))
            {            
                // работаем с пользователями - получем их ид
                $keys = array_keys($formdata->users);
                $actsel = '';
                switch($actselect)
                {
                    case '0': case '1':
                        $actsel = '3';
                        break;
                    case '2':
                        $actsel = '4';
                        break;

                }
                if($actsel == '3' || ($actsel == '4' && $confirm))
                {              
                    $cohorts = $DB->get_records_sql('SELECT userid FROM {cohort_members} WHERE cohortid = ?', array($formdata->cohortmenu));
                    $userids = array();
                    foreach($keys as $userid)
                    {
                        if($actsel == '4' && !array_key_exists(''.$userid, $cohorts))
                        {
                             $userids[] = $userid;
                             $errors = 'errr!!';
                        }
                        elseif($actsel == '3' && array_key_exists(''.$userid, $cohorts))
                        {
                            $userids[] = $userid;
                            $errors = 'errr!!';
                        }
                    }
                }
                $ans = '';
                // выполнение выбранных действий, для удаления выполняем, только когда подтвердили
                if(($actsel != '4' && !$confirm) || ($actsel == '4' && $confirm))
                    $ans = ExecuteAct($formdata, $actsel, $info);
                //redirect($PAGE->url, "Действие выполнено",  null, 'success');
                if($errors == '' && strpos($ans, 'OK') !== FALSE)
                {
                    echo $OUTPUT->notification("Действие выполнено",'success');
                }
                elseif($errors != '' && $actsel == '4')
                {
                    $errors = GetStringUserName($userids);
                    $cohort = $DB->get_record('cohort', array('id' => $formdata->cohortmenu) );
                    //вывод о том, что студенты не содержатся в группе
                    echo $OUTPUT->notification(get_string('unenrollcohortwarning', 'report_report_pstgu_deanery', $cohort->name) . $errors,'warning');
                }
                elseif($errors != '' && $actsel == '3')
                {
                    $errors = GetStringUserName($userids);
                    $cohort = $DB->get_record('cohort', array('id' => $formdata->cohortmenu) );
                    //вывод о том, что студенты содержатся в группе
                    echo $OUTPUT->notification(get_string('enrollcohortwarning', 'report_report_pstgu_deanery', $cohort->name) . $errors,'warning');           
                }                
            }
            else
            {        
                echo $OUTPUT->notification(get_string('errornotification', 'report_report_pstgu_deanery'),'error');        
            }
        }
        if(isset($formdata->users) && !isset($formdata->selectfirst) && !isset($formdata->selectall) && !isset($formdata->disselect))
        {
            
            //после выполнения действия еще ра выберем, чтобы не нарушалась сортировка
            $CheckedUsers = GetFiltredGrades($params[0], $course->id, $roleid, $checkcount, $sort, $selectA, $TotalGrade->id, array_keys($formdata->users));
        }
        if(isset($formdata->selectfirst))
        {
            //берем массив оценок за курс студентов прошедших условия (значание оценок или время завершения)
            if(isset($formdata->users))
            {                
                //после выполнения действия еще ра выберем, чтобы не нарушалась сортировка
                $CheckedUsers = GetFiltredGrades($params[0], $course->id, $roleid, $checkcount, $sort, $selectA, $TotalGrade->id, array_keys($formdata->users));
            }
            else
            {
                $CheckedUsers = GetFiltredGrades($params[0], $course->id, $roleid, $checkcount, $sort, $selectA, $TotalGrade->id);
            }
        }
        if(isset($formdata->selectall))
        {
            $CheckedUsers = array();
        }
        if(isset($formdata->disselect))
        {
            if(isset($formdata->users))
                unset($formdata->users);
        }
    }
    //берем массив оценок за курс студентов не прошедших условия (значанеие оценок или время завершения)
    $UnCheckedUsers = array();
    $UnCheckedUsers = MergeUserList(array_keys($CheckedUsers), $TotalGrade->id, $course->id, $roleid, $params[0], $course_ciph->cipher);

    $userlist = array();

    foreach($CheckedUsers as $key => $value)
    {
        $userlist[$key] = $value;
    }

    foreach($UnCheckedUsers as $key => $value)
    {
        $userlist[$key] = $value;
    }
//    //получили учебные группы по каждому пользоваетелю
//    if($actselect == '0')
//        $usercohort = GetUserCohort($userlist, $course_ciph->cipher);
//    else
//        $usercohort = GetUserCohort($userlist, $course_ciph->year);
    $usercohort = GetUserCohort($userlist);
    if ($isseparategroups and (!$currentgroup) ) {
        // The user is not in the group so show message and exit.
        echo $OUTPUT->heading(get_string("notingroup"));
        echo $OUTPUT->footer();
        exit;
    }

    // Get the hidden field list.
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();  // Teachers and admins are allowed to see everything.
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    if (isset($hiddenfields['lastaccess'])) {
        // Do not allow access since filtering.
        $accesssince = 0;
    }

    // Таблица с элементами управления Выводится в верхней части таблицы с юзерами  
    // Print settings and things in a table across the top.
    // ячейка пустая, чтобы сохранить выранивание слева
    $emptycell = new html_table_cell('&nbsp;');
    $emptycell->attributes['class'] = 'left';

    //надпись с описанием действий
    $tabinfocell = new html_table_cell(html_writer::label(get_string('descriptionactions', 'report_report_pstgu_deanery'), 'id_selectform'));
    $tabinfocell->attributes['class'] = 'left';

    //
    $helpactselectcell = new html_table_cell($OUTPUT->help_icon('helpinfotab2', 'report_report_pstgu_deanery', ''));
    $helpactselectcell->attributes['class'] = 'left';
    $helpactselectcell->attributes['width'] = '25';

    $tableinfo = new html_table();
    $tableinfo->attributes['class'] = 'controls';
    $tableinfo->width = '364';
    $tableinfo->data[] = new html_table_row();
    $tableinfo->data[0]->cells[] = $tabinfocell;
    $tableinfo->data[0]->cells[] = $helpactselectcell;
    $tableinfo->data[0]->cells[] = $emptycell;
    

    //собираем надпись с шифром программы
    $cipherprogrammlabel = 'Шифр программы: <b>' . $course_ciph->cipher. '</b>&nbsp;&nbsp;&nbsp;&nbsp;Год: <b>' . $course_ciph->year . '</b>';
    //надпись с шифром программы и годом
    $cipherprogrammlabelcell = new html_table_cell( html_writer::label($cipherprogrammlabel, ''));
    $cipherprogrammlabelcell->attributes['class'] = 'left';

    $cipherprogrammlabelinfocell = new html_table_cell($OUTPUT->help_icon('helpcipher', 'report_report_pstgu_deanery', ''));
    $cipherprogrammlabelinfocell->attributes['class'] = 'left';

    $ciphertable = new html_table();
    $ciphertable->attributes['class'] = 'controls';
    $ciphertable->data[] = new html_table_row();
    $ciphertable->data[0]->cells[] = $cipherprogrammlabelcell;
    $ciphertable->data[0]->cells[] = $cipherprogrammlabelinfocell;
    $ciphertable->data[0]->cells[] = $emptycell;

    // ячейка для кнопки
    //url для кнопки
    $formaction = new moodle_url('/report/report_pstgu_deanery/index.php', array('page' => 2));
    // Kнопка
    $buttoncell = new html_table_cell('<input name="selectfirst" for="participantsform" type="submit" formaction="'.$formaction.'" value="Выбрать первых">');
    $buttoncell->attributes['class'] = 'left';
    // ячейка для текстового поля
    $textboxcell = new html_table_cell('<input for="participantsform" type="text" id="users1" name="checkcount"  value="'.s($checkcount).'"  size="1"/>');
    $textboxcell->attributes['class'] = 'left';
    //ячейка для селекта
    $options = array( 1 => 'с наибольшими оценками' );
    $selectcell = new html_table_cell(html_writer::select($options, 'selectA', $selectA, array(0 => 'завершивших курс')));
    $selectcell->attributes['class'] = 'left';

    $btnselectall = new html_table_cell('<input name="selectall" type="submit" for="participantsform" formaction="'.$formaction.'" value="Выбрать всех" /> ');
    $btnselectall->attributes['class'] = 'left';

    $btnunselectall = new html_table_cell('<input name="disselect" type="submit" for="participantsform" formaction="'.$formaction.'" value="Очистить выбор" /> ');
    $btnunselectall->attributes['class'] = 'left';
    
//    $btnsavecell = new html_table_cell(html_writer::tag('input','',array('name'=>'save','type'=>'submit','for'=>'participantsform','formaction'=>$baseurl,'value'=>'Сохранить измененные заметки')));
//    $btnsavecell->attributes['class'] = 'left';
    /**
     *  Таблица для выбора студентов по критерию
     */
    $tableselectfirst = new html_table();
    $tableselectfirst->attributes['class'] = 'controls';
    $tableselectfirst->width = '600';
    $tableselectfirst->data[] = new html_table_row();
    $tableselectfirst->data[0]->cells[] = $buttoncell;
    $tableselectfirst->data[0]->cells[] = $textboxcell;
    $tableselectfirst->data[0]->cells[] = $selectcell;
    $tableselectfirst->data[0]->cells[] = $btnselectall;
    $tableselectfirst->data[0]->cells[] = $btnunselectall;
    //$tableselectfirst->data[0]->cells[] = $btnsavecell;
    $tableselectfirst->data[0]->cells[] = $emptycell;
    /**
     * добавли таблицу для чекбокса сортировки
     */
    $tablesortfio = new html_table();
    $tablesortfio->attributes['class'] = 'controls';
    $tablesortfio->width = '343';
    $tablesortfio->data[] = new html_table_row();
    $chbcell = new html_table_cell(html_writer::checkbox('sort', 1, $sort, 'При выборе первых сортировать их по ФИО'));
    //если пустая, то ставим отмечено
    //$sort = is_null($sort) ? 1 : $sort;
    $chbcell->attributes['class'] = 'left';

    $sortinfocell = new html_table_cell($OUTPUT->help_icon('helpsortfio', 'report_report_pstgu_deanery', ''));
    $sortinfocell->attributes['class'] = 'left';

    $tablesortfio->data[0]->cells[] = $chbcell;
    $tablesortfio->data[0]->cells[] = $sortinfocell;
    $tablesortfio->data[0]->cells[] = $emptycell;
    
    /**
    * Таблица для действий
    */
    $submitformtable = new html_table();
    $submitformtable->attributes['class'] = 'controls';
    //ячейка с надписью для выпадающего меню с набором действий
    $actselectlabelcell = new html_table_cell(html_writer::label('Действие ', 'actselect'));
    $actselectlabelcell->attributes['class'] = 'left';
    $actselectlabelcell->attributes['valign'] = 'top';
    //$actselectlabelcell->attributes['width'] = '50';
    //справка к действию
    $actioninfocell = new html_table_cell($OUTPUT->help_icon('helplabelactiontab2', 'report_report_pstgu_deanery', ''));
    $actioninfocell->attributes['class'] = 'left';
    $actioninfocell->attributes['valign'] = 'top';
    //$actioninfocell->attributes['width'] = '25';
    //ячейка для выпадающего меню с набором действий
    $options = array();
    $options[0] = get_string('actselectaddtocohort', 'report_report_pstgu_deanery');
    $options[1] = 'Зачислить в подгруппу';
    $options[2] = get_string('actselectremovefromcohort', 'report_report_pstgu_deanery');

    $SelActionsForm = $OUTPUT->single_select($formaction, 'actselect',$options, $actselect, null, 'actionform', array('label' => ''));
    //            html_writer::select($options, 'actselect', $actselect, null );
    $actselectcell = new html_table_cell($SelActionsForm);
    $actselectcell->attributes['class'] = 'left';
    $actselectcell->attributes['valign'] = 'top';
    //$actselectcell->attributes['width'] = '150';
    //$attr = array('onchange' => 'report_pstgu_deanery_disabled_if()');
    //получили список глобальных групп пользователей на курсе
    if($actselect == 0)
        $key = $course_ciph->cipher;
    else
        $key = $course_ciph->year;
    $result = GetCohorts($key);   
    $celltext = html_writer::select($result, 'cohortmenu', '', true);
    // Ячейка  для меню с группами
    $cohortmenucell = new html_table_cell($celltext);
    $cohortmenucell->attributes['class'] = 'left';
    $cohortmenucell->attributes['valign'] = 'top';
    //$cohortmenucell->attributes['width'] = '100';

    $cohortinfocell = new html_table_cell($OUTPUT->help_icon('helpcohorttab2', 'report_report_pstgu_deanery', ''));
    $cohortinfocell->attributes['class'] = 'left';
    $cohortinfocell->attributes['valign'] = 'top';
    //$cohortinfocell->attributes['width'] = '25';

    $btncell = new html_table_cell('<input name="execaction" type="submit"  for="participantsform" formaction="'.$baseurl.'" id="id_submitbutton" value="'.get_string('execact', 'report_report_pstgu_deanery').'">');
    $btncell->attributes['class'] = 'left';
    $btncell->attributes['valign'] = 'top';
    //$btncell->attributes['width'] = '75';
    //$submitformtable
    $submitformtable->data[] = new html_table_row();//новый ряд 
    $submitformtable->data[0]->cells[] = $actselectlabelcell;
    $submitformtable->data[0]->cells[] = $actioninfocell;
    $submitformtable->data[0]->cells[] = $actselectcell;
    $submitformtable->data[0]->cells[] = $cohortmenucell;
    $submitformtable->data[0]->cells[] = $cohortinfocell;
    $submitformtable->data[0]->cells[] = $btncell;    
    $submitformtable->data[0]->cells[] = $emptycell;        
    
    // форма с таблицей пользователей
    if ($bulkoperations) 
    {  
        echo '<div>';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';    
    }

    echo '<input type="hidden" name="id" value="'.$course->id.'" />';
    echo html_writer::table($tableinfo);
    echo html_writer::table($ciphertable);
    echo html_writer::table($tablesortfio);
    echo html_writer::table($tableselectfirst);
    echo html_writer::table($submitformtable); 
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';    
    
    echo '<div class="userlist">';
        // Define a table showing a list of users in the current role selection.
    $tablecolumns = array();
    $tableheaders = array();

    if ($bulkoperations) 
    {
        $tablecolumns[] = 'select';
        $tableheaders[] = get_string('select');
    }
    //обозначаем столбцы
    $tablecolumns[] = 'userpic';
    $tablecolumns[] = 'serialnumber';
    $tablecolumns[] = 'lastname';
    $tablecolumns[] = 'timecomplited';
    $tablecolumns[] = 'finalgrade';
    $tablecolumns[] = 'cohort';
    $tablecolumns[] = 'op_completion';
    $tablecolumns[] = 'ok_completion';
    $tablecolumns[] = 'notes';
    //задаем название столбцов
    $tableheaders[] = 'Фото';//get_string('userpic');
    $tableheaders[] = 'п/п';
    $tableheaders[] = 'ФИО';
    $tableheaders[] = 'Завершение курса';
    $tableheaders[] = 'Оценка за курс';
    $tableheaders[] = 'Учебная группа';
    $tableheaders[] = 'Оценки за ОП';
    $tableheaders[] = 'Оценки за ОК';
    $tableheaders[] = 'Заметки (кол-во)';


    $table = new flexible_table('user-index-participants-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());
    //устанавливаем сортировку по фамилии, столбец сортировки по умолчинию и направление сортировки
    //$table->sortable(true, 'lastname', SORT_ASC);

    //задаем несортабельные столбцы
    $table->no_sorting('select');
    $table->no_sorting('serialnumber');
    $table->no_sorting('lastname');
    $table->no_sorting('timecomplited');
    $table->no_sorting('finalgrade');
    $table->no_sorting('cohort');
    $table->no_sorting('op_completion');
    $table->no_sorting('ok_completion');


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
    //режим редактирования
    $editlink = '';
    if ($course->id != SITEID && has_capability('moodle/course:enrolreview', $context)) {
        $editlink = new moodle_url('/enrol/users.php', array('id' => $course->id));
    }

    // Show a search box if all participants don't fit on a single screen.
    /*if ($totalcount > $perpage)
    {
        echo '<form action="export_ispk2.php" class="searchform"><div><input type="hidden" name="id" value="'.$course->id.'" />';
        echo '<label for="search">' . get_string('search', 'search') . ' </label>';
        echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'.get_string('search').'" /></div></form>'."\n";
    }*/


    //заливаем список пользователей в таблицу
    //таблица 
    $notes_cnt = load_note($course->id, array_keys($userlist));
    if ($userlist) 
    {
        //получаем объект с настройками журнала оценок по курсу
        $gradebook = grade_item::fetch_all(array('courseid'=>$course->id)); 
        $gradeitem =  $gradebook[$TotalGrade->id];
        //распечатываем таблицу со студентами
        $usersprinted = array();    
        $i = 1;
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
                $profilelink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a>';
            } else 
            {
                $profilelink = fullname($user);
            }

            //масcив ячеек для строки
            $data = array();
            $is_last_check = FALSE;

            if($formdata && isset($formdata->users))
            {
                $is_last_check =   array_key_exists($user->id, $formdata->users);
            }        

            if ($bulkoperations) 
            {
                if ($selectall || ($checkcount > 0 && array_key_exists($user->id, $CheckedUsers) || $is_last_check)) 
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
            // 3 добавили порядковый номер
            $data[] = $i; 
            // 4 добавили имя 
            $data[] = $profilelink;
            // 6 дата если студент содежится в массиве с оценками
            if(array_key_exists($user->id, $CheckedUsers))
            {               
                //проводим такую проверку, т.к. из базы выбираем либо с оценками, либо с датой завершения курса
                if(isset($CheckedUsers[$user->id]->timecompleted))
                {                
                    $data[] =  date('d.m.y H:i:s', $CheckedUsers[$user->id]->timecompleted);
                }
                else
                {
                    $data[] = '-';
                }
                if(isset($CheckedUsers[$user->id]->grade))
                {
                    $formattedgrade = grade_format_gradevalue($CheckedUsers[$user->id]->grade, $gradeitem, false, '1', 2);
                }
                else
                {
                    $formattedgrade = '-';
                }
            }
            else//если студент не попал под условия, то просто выведем что у него есть
            {
                if(isset($user->timecompleted))
                {
                    $data[] =  $user->timecompleted;                
                }
                else
                {
                    $data[] = '-';
                }
                if(isset($user->grade))
                {
                    $formattedgrade = grade_format_gradevalue($user->grade, $gradeitem, false, '1', 2);
                }
                else
                {
                    $formattedgrade = grade_format_gradevalue(null, $gradeitem, false, '1', 2);
                }
            }
            // 7 оценка
            $data[] = $formattedgrade; 
            // 8 имя группы
            if(isset($usercohort[$user->id]->name))
                $data[] = $usercohort[$user->id]->name;
            else
                $data[] = '-'; 
            // 9 Оценки за ОП
            if(isset($user->op_completion))
                $data[] = $user->op_completion;
            else
                $data[] = '-'; 
            // 10 Оценки за ОК
            if(isset($user->ok_completion))
                $data[] = $user->ok_completion;
            else
                $data[] = '-';
            // 11 столбец сс заметкой 
            $note_url = $CFG->wwwroot."/notes/index.php?user=$user->id&course=$course->id";           
            if(isset($notes_cnt[$user->id]))
            {
                $cnt = $notes_cnt[$user->id]->cnt ;
                $data[] = html_writer::tag('a','Заметок:'.$cnt , array('href'=>$note_url, 'target'=>'_blank', 'title' => 'Посмотреть заметки пользователя'));
            }
            else
            {
                $data[] = html_writer::tag('a','Заметок:0', array('href'=>$note_url, 'target'=>'_blank', 'title' => 'Посмотреть заметки пользователя'));
            }
            //добавили строку в таблицу
            $table->add_data($data);
            $i++;
        }
        //освобождаем ресурсоемкие переменные
        unset($gradeitem);
        unset($gradebook);
        unset($usersprinted);
    }
    //вывод таблицы
    $table->print_html();
    echo '</form>';
    //копируеем ссылку
    $perpageurl = clone($baseurl);    

    //если студентов больше чем должно поместиться на странице
    /*else if ($matchcount > 0 && $perpage < $matchcount) 
    {
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        //показать все столько-то человек
        echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showall', '', $matchcount)), array(), 'showall');
    }*/
    //кнопки для массовых операций
    if ($bulkoperations) 
    {   
        echo '<noscript style="display:inline">';
        echo '<input type="submit" value="'.get_string('ok').'" />';
        echo '</noscript>';    
        echo '<input type="hidden" name="id" value="'.$course->id.'" />'; 
        echo '</div>';
        $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
        $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
    }
    echo '</div>';  // Userlist.

    if (isset($userlist)) 
    {
        unset($userlist);
    }
    if(isset($table))
    {
        unset($table);
    }
}