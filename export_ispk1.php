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



function export_ispk1($course, $context)
{
    global $OUTPUT, $USER, $CFG, $PAGE;
    $roleid       = 5; // здесь нам нужны только студенты
    $selectall    = optional_param('selectall', false, PARAM_BOOL); // When rendering checkboxes against users mark them all checked.
    $confirm      = optional_param('confirm', 0, PARAM_BOOL);
    $actselect    = optional_param('actselect', 0, PARAM_INT);
    $execaction = optional_param('execaction', null, PARAM_RAW);
    // проверим, может ли
    //bulk operations - массовые операции
    $bulkoperations = has_capability('moodle/course:bulkmessaging', $context);
    // Check to see if groups are being used in this course
    // and if so, set $currentgroup to reflect the current group.
    $groupmode    = groups_get_course_groupmode($course);   // Groups are being used.
    $currentgroup = groups_get_course_group($course, true);
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
    
    $baseurl = new moodle_url
    (
        '/report/report_pstgu_deanery/index.php', 
        array
        (
            'contextid' => $context->id,
            'roleid' => $roleid,
            'id' => $course->id,
            'page' => 1
        )
    );
    // Извлекли шифр курса
    $course_ciph = extract_course_cipher($arr);
    // Таблица с описанием вкладки
    $infotable = new html_table();
    $infotable->attributes['class'] = 'controls';
    $infotable->width = '670';
    // Таблица с шифром программы
    $ciphertable = new html_table();
    $ciphertable->attributes['class'] = 'controls';
    $ciphertable->width = '300';
    // Таблица для действий
    $submitformtable = new html_table();
    $submitformtable->attributes['class'] = 'controls';
    $submitformtable->with = '450';
    // Ячейки
    // ячейка пустая, чтобы сохранить выранивание слева
    $emptycell = new html_table_cell('&nbsp;');
    $emptycell->attributes['class'] = 'left';
    //надпись с описанием действий
    $infocell = new html_table_cell( html_writer::label(get_string('descriptionactionstab1', 'report_report_pstgu_deanery'), 'participantsform'));
    $infocell->attributes['class'] = 'left';
    //инфо для подробного описания действий
    $helpiconcell = new html_table_cell($OUTPUT->help_icon('helpinfotab1', 'report_report_pstgu_deanery', ''));
    $helpiconcell->attributes['class'] = 'left';
    //добавили строку в таблицу, а потом в нее наши ячейки
    $infotable->data[] = new html_table_row();
    $infotable->data[0]->cells[] = $infocell;
    $infotable->data[0]->cells[] = $helpiconcell;
    $infotable->data[0]->cells[] = $emptycell;
    //собираем надпись с шифром программы
    $cipherprogrammlabel = 'Шифр программы: <b>' . $course_ciph->cipher . '</b>&nbsp;&nbsp;&nbsp;&nbsp;Год: <b>' . $course_ciph->year. '</b>';
    //надпись с шифром программы и годом
    $ciphercell = new html_table_cell(html_writer::label($cipherprogrammlabel, ''));
    $ciphercell->attributes['class'] = 'left';
    //справка по шифру программы
    $cipherheplcell = new html_table_cell($OUTPUT->help_icon('helpcipher', 'report_report_pstgu_deanery', ''));
    $cipherheplcell->attributes['class'] = 'left';
    //добавили
    $ciphertable->data[] = new html_table_row();
    $ciphertable->data[0]->cells[] = $ciphercell;
    $ciphertable->data[0]->cells[] = $cipherheplcell;
    $ciphertable->data[0]->cells[] = $emptycell;            
    // расстояние между ячейками
    $submitformtable->data[] = new html_table_row();//новый ряд 
    //ячейка с надписью для выпадающего меню с набором действий
    $actselectlabelcell = new html_table_cell();
    $actselectlabelcell->attributes['class'] = 'left';
    $actselectlabelcell->attributes['width'] = '50';
    $actselectlabelcell->text = html_writer::label('Действие ', 'actselect');
    //ячейка для выпадающего меню с набором действий
    $actselectcell = new html_table_cell();
    $actselectcell->attributes['class'] = 'left';
    $actselectcell->attributes['width'] = '100';
    $options = array();
    $options[1] = get_string('actselectdelete', 'report_report_pstgu_deanery');
    $options[2] = get_string('actselectupdate', 'report_report_pstgu_deanery');
    //$attr = array('onchange' => 'report_pstgu_deanery_disabled_if()');
    $actselectcell->text = html_writer::select($options, 'actselect', '', array(0 => get_string('actselectcreate', 'report_report_pstgu_deanery'))/*, $attr*/);
    // кнопка отправки формы
    $btncell = new html_table_cell();    
    $btncell->text = '<input name="execaction" type="submit"  form="participantsform" formaction="'.$baseurl.'" id="id_submitbutton" value="'. get_string('execact', 'report_report_pstgu_deanery').'" />';
    $btncell->attributes['class'] = 'left';
    //добавили
    $helpactselectcell = new html_table_cell($OUTPUT->help_icon('helpactselect', 'report_report_pstgu_deanery', ''));
    $helpactselectcell->attributes['class'] = 'left';
    $helpactselectcell->attributes['width'] = '25';
    $submitformtable->data[0]->cells[] = $actselectlabelcell;
    $submitformtable->data[0]->cells[] = $helpactselectcell;
    $submitformtable->data[0]->cells[] = $actselectcell;
    $submitformtable->data[0]->cells[] = $btncell;    
    $submitformtable->data[0]->cells[] = $emptycell;   
     
    //data processing
    $info = array();
    //массив для выбранных студентов
    $CheckedUsers = array();
    // Оперции на получение пользователей, оценок и дат заврешений, названий глобальных групп
    $params = array();
    //получаем параметры контекста для данной роли на данном курсе
    $params = array_merge($params, $context->get_parent_context_ids(true));
    // выводим нашу форму в одном из двух режимов
    echo '<form action="action_redir.php" method="post" id="participantsform" >';
    // получили данные с предыдущей отправки формы для выделения пользователей
    if ($formdata = data_submitted() and confirm_sesskey()) 
    {   
        if(isset($formdata->execaction) || isset($execaction))
        {
            if($actselect == 1 && !$confirm)
            {
                $message = 'Вы действиетельно хотите удалить абитуриентов и заявления из ИС ПК?';

                echo $OUTPUT->heading('Удаление абитуриентов и заявлений');

                $confirmform = '';          

                $continue = new moodle_url("$CFG->wwwroot/report/report_pstgu_deanery/index.php", array(
                                                'actselect' => $actselect,
                                                'id' => $course->id,
                                                'confirm'=>1,
                                                'execaction'=>'exec',
                                                'page' => 1));             
                $return = new moodle_url("$CFG->wwwroot/report/report_pstgu_deanery/index.php", array(
                                                'actselect' => '0',
                                                'id' => $course->id,
                                                'page' => 1));
                //заголовок                
                $confirmform .= html_writer::tag('h4', get_string('confirm'));
                 //сообщение
                $confirmform .= html_writer::tag('p', $message);
                 //кнопки
                $confirmform .= '<input type="submit" formaction="'.$continue->out(). '" value="'.get_string('yes').'"/>';
                $confirmform .= '<input type="submit" formaction="'.$return->out(). '" value="'.get_string('no').'"/>';
                $confirmform .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
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
            $errors = '';
            if(isset($formdata->users))
            {
                // работаем с пользователями - получем их ид
                $keys = array_keys($formdata->users);
                $ans = '';
                if(($actselect == '0' || ($actselect == '1' && $confirm) || $actselect == '2'))
                {
                    // выполнение выбранных действий
                    $ans = ExecuteAct($formdata, $actselect, $info);
                }
                if(strpos($ans, 'OK') === FALSE)
                {
                    echo $OUTPUT->notification($ans, 'info');
                }
            }
            else
            {
                echo $OUTPUT->notification(get_string('errornotification', 'report_report_pstgu_deanery'),'error');        
            }
        }
        if(isset($formdata->selectcomplcourse))
        {
            //берем массив студентов завершивших курс
            $CheckedUsers = get_course_studentnumbers($params[0], $course->id, $roleid, $course_ciph->cipher);
            //снимем предыдущее выделение
            if(isset($formdata->users))
            {
                unset($formdata->users);
            }
        }
        elseif(isset($formdata->selectall))
        {
            $CheckedUsers = array();
        }
        elseif(isset($formdata->disselect))
        {
            if(isset($formdata->users))
                unset($formdata->users);
        }       

    }
    //отрисовка таблиц
    //инфо
    echo html_writer::table($infotable);
    //шифр
    echo html_writer::table($ciphertable);
    // форма с таблицей пользователей
    if ($bulkoperations) 
    {    
        echo '<div>';
        echo html_writer::tag('input', '', array('name'=>'selectall','value'=>'Выбрать всех','for'=>'participantsform', 'formaction'=>$baseurl, 'type'=>'submit'));
        echo html_writer::tag('input', '', array('name'=>'selectcomplcourse','value'=>'Выбрать завершивших курс','for'=>'participantsform','formaction'=>$baseurl, 'type'=>'submit'));
        echo html_writer::tag('input', '', array('name'=>'disselect','value'=>'Очистить выбор','for'=>'participantsform','formaction'=>$baseurl,'type'=>'submit'));
        // таблица с действиями
        echo html_writer::table($submitformtable);
        // скрытые поля
        echo html_writer::tag('input', '', array('type'=>'hidden','name'=>'sesskey','value'=>sesskey()));
        echo html_writer::tag('input', '', array('type'=>'hidden','name'=>'returnto','value'=>s($PAGE->url->out(false))));
    }

    //берем массив оценок за курс студентов не прошедших условия (значанеие оценок или время завершения)
    $UnCheckedUsers = array();
    $UnCheckedUsers = MergeUserList(array_keys($CheckedUsers), 0, $course->id, $roleid, $params[0], $course_ciph->cipher);

    $userlist = array();

    foreach($CheckedUsers as $key => $value)
    {
        $userlist[$key] = $value;
    }

    foreach($UnCheckedUsers as $key => $value)
    {
        $userlist[$key] = $value;
    }
    //получили учебные группы по каждому пользоваетелю
    $usercohort = GetUserCohort($userlist, $course_ciph->cipher);
    echo '<div class="userlist">';
    if ($isseparategroups and (!$currentgroup) ) {
        // The user is not in the group so show message and exit.
        echo $OUTPUT->heading(get_string("notingroup"));
        echo $OUTPUT->footer();
        exit;
    }


    // Таблица с элементами управления Выводится в верхней части таблицы с юзерами  
    // Print settings and things in a table across the top.
    // ячейка пустая, чтобы сохранить выранивание слева
    // Define a table showing a list of users in the current role selection.
    $tablecolumns = array();
    $tableheaders = array();
    //обозначаем столбцы
    //галочка
    if ($bulkoperations) 
    {
        $tablecolumns[] = 'select';
        $tableheaders[] = get_string('select');
    }
    //фото
    $tablecolumns[] = 'userpic';
    //порядковый номер
    $tablecolumns[] = 'serialnumber';
    //ФИО
    $tablecolumns[] = 'lastname';
    //время завершения
    $tablecolumns[] = 'timecomplited';
    //номер ЛД
    $tablecolumns[] = 'privatnumber';
    //результат экспорта
    $tablecolumns[] = 'exportresult';
	
    //задаем название столбцов
    $tableheaders[] = 'Фото'; //get_string('userpic');
    $tableheaders[] = '№ п/п';
    $tableheaders[] = 'ФИО';
    $tableheaders[] = 'Завершение курса';
    $tableheaders[] = 'Номер ЛД из ИС ПК';
    $tableheaders[] = 'Результат действия';
    
    $table = new flexible_table('user-index-participants-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());
    //устанавливаем сортировку по фамилии, столбец сортировки по умолчинию и направление сортировки
    //$table->sortable(true, 'lastname', SORT_ASC);

    //задаем несортабельные столбцы
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
    //режим редактирования
    $editlink = '';
    if ($course->id != SITEID && has_capability('moodle/course:enrolreview', $context)) {
        $editlink = new moodle_url('/enrol/users.php', array('id' => $course->id));
    }

    
    //заливаем список пользователей в таблицу
    //таблица 
    
    if ($userlist) 
    {
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
            // 3 добавили порядковый номер
            $data[] = $i;        
            //$data[2]->attributes['class'] = 'right';
            // 4 добавили имя 
            $data[] = $profilelink;                  
            // 5 дата если студент содежится в массиве с оценками
            if(array_key_exists($user->id, $CheckedUsers))
            {               
                //проводим такую проверку, т.к. из базы выбираем либо с оценками, либо с датой завершения курса
                if(isset($CheckedUsers[$user->id]->timecompleted))
                {                
                    $data[] =  $CheckedUsers[$user->id]->timecompleted;
                }
                else
                {
                    $data[] = '-';
                }            
            }
            else//если студент не попал под условия, то просто выведем что у него есть
            {
                if(isset($user->timecompleted))
                {
                    $data[] = $user->timecompleted;                
                }
                else
                {
                    $data[] = '-';
                }            
            }              
            // 6 номер личного дела
            if(isset($user->privatnumber))
                $data[] = $user->privatnumber;
            else
                $data[] = '-';
            // 7 результат экспорта в ИСПК
            if(isset($info[$user->id]))
                $data[] = $info[$user->id];
            else
                $data[] = '-';            
            //добавили строку в таблицу
            $table->add_data($data);
            $i++;
        }
        //освобождаем ресурсоемкие переменные
        unset($usersprinted);
    }
    //вывод таблицы
    $table->print_html();

    //кнопки для массовых операций
    if ($bulkoperations) 
    {   
        //кнопки    
        echo '<noscript style="display:inline">';
        echo '<input type="submit" value="'.get_string('ok').'" />';
        echo '</noscript>';    
        echo '<input type="hidden" name="id" value="'.$course->id.'" />'; 
        echo '</div></form>';          
    }
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