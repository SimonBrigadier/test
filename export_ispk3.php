<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function export_ispk3($course, $context)
{
    global $OUTPUT, $CFG, $PAGE;
    
    $courseid     =  $course->id;
    
    if((int)date('m') < 9)
        $selectyearsdodefault = ((int)date("Y") - 1).'-'.(int)date("Y");
    else
        $selectyearsdodefault = (int)date("Y").'-'. ((int)date("Y") - 1);

    $selectyearsdo       = optional_param('selectyearsdo',$selectyearsdodefault , PARAM_RAW); // Учебный год в СДО


    $baseurl = new moodle_url('/report/report_pstgu_deanery/index.php', array(        
            'id' => $course->id,
            'selectyearsdo' => s($selectyearsdo),
            'page' => 3));

    $PAGE->set_url('/report/report_pstgu_deanery/index.php', array('id' => $courseid,'string' => $selectyearsdo, 'page' => 3));
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

    //$is_null_groups = IsNullGroups($xml);
    //if($is_null_groups != '')
    //{   
    //    echo $OUTPUT->notification("В ИС Деканат открыты следующие учебные годы: $is_null_groups Но в них нет групп с шифром: $course_ciph->cipher ",'warning');
    //}
    //получили список глобальных групп пользователей на курсе по текущему году
    $sysdo = preg_split('/[^\w]+/u', $selectyearsdo);
    if((int)date('m') < 9)
        $c = $course_ciph->cipher . '-' . $sysdo[0];
    else
        $c = $course_ciph->cipher . '-' . $sysdo[1];
    $result = GetCohorts($c, true);

    $Enabled = '';
    //массив выпадающих списков
    $groupdeaneryoptions = array();
    GetGropMenu($result, $groupdeaneryoptions, $course_ciph->cipher);
    // получили данные с предыдущей отправки формы для выделения пользователей
    if ($formdata = data_submitted() and confirm_sesskey()) 
    {      
        if(isset($formdata->foundbutton))
        {
            $s = preg_split('/[^\w]+/u', $formdata->selecеisyear);
            if((int)date('m') < 9)
                $selecеisyear = $s[0].'';
            else
                $selecеisyear = $s[1].'';
            $selectionsyear = array();
            // выполнение выбранных действий
            $ans = FindMatch($selecеisyear, $formdata->selectsemestr, $result, $groupdeaneryoptions, $selectionsyear);
            switch($ans)
            {
                case 'OK':
                    echo $OUTPUT->notification("Соответствие найдено",'success');
                    break;
                case 'Worning':
                    echo $OUTPUT->notification("Не для всех групп найдено соответствие.",'warning');
                    break;
            }

        }
        elseif(isset($formdata->submitbutton))
        {
            if(SaveRelations($formdata, $result))
                echo $OUTPUT->notification("Соответствие сохранено",'success');
            else
                echo $OUTPUT->notification("Сехранение не выполнено, не для всех групп установлено соответствие.",'error');
            //после сохранения обновим выпадающие меню с группами
            $result = array();
            $result = GetCohorts($c, true);
        }

    }
    else//если данные не отправляли, только загрузили страницу. 
    {
        //загрузили по все по умолчанию
        if(count($result) == 0)
        {
            echo $OUTPUT->notification("В СДО нет групп с шифром : $course_ciph->cipher и годом набора: $selectyearsdo",'error');
            $Enabled = 'disabled="disabled"';        
        }
    }

    //вывод инфо и шифра программы
    echo html_writer::label(get_string('info', 'report_report_pstgu_deanery'), '');
    echo html_writer::label('<p>Шифр программы: <b>'.$course_ciph->cipher.'</b></p>', '');
    // ячейка пустая, чтобы сохранить выранивание слева
    $emptycell = new html_table_cell('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    $emptycell->attributes['class'] = 'left';
    $emptycell->attributes['width'] = '50';

    echo '';
        $yeartable = new html_table();
        $yeartable->attributes['class'] = 'controls';
        $yeartable->cellspacing = 0;// расстояние между ячейками
        $yeartable->data[] = new html_table_row();//новый ряд

        $yearlabelcell = new html_table_cell();
        $yearlabelcell->attributes['class'] = 'left';
        //$yearlabelcell->attributes['width'] = '50';
        $yearlabelcell->text = html_writer::label('1.Выберите учебный год набора на программу в СДО:&nbsp;&nbsp;', 'selectyear');

        $yearselectcell = new html_table_cell();
        $yearselectcell->attributes['class'] = 'left';
        //собираем меню для групп из деканата
        $optionsDeanYear = array();
        //получаем года c edvp
        GetYear($optionsDeanYear, $course_ciph->cipher);

        if(!isset($formdata->selecеisdeanery))
            $i = GetTempYearIndex($optionsDeanYear);
        else
            $i = $formdata->selecеisdeanery;
        $SelYearsForm =  '<div class="yearsform">';
        $SelYearsFormURL = new moodle_url("$CFG->wwwroot/report/report_pstgu_deanery/index.php",array('id' => $courseid, 'page' => 3));
        $SelYearsForm .= $OUTPUT->single_select($SelYearsFormURL, 'selectyearsdo', $optionsDeanYear, $selectyearsdo, null, 'yearsform', array('label' => ''));
        $SelYearsForm .= '</div>';
        $yearselectcell->text = $SelYearsForm; 
        //html_writer::select($optionsDeanYear, 'selectyear', $i.'', null);

        $yeartable->data[0]->cells[] = $yearlabelcell;
        $yeartable->data[0]->cells[] = $yearselectcell;
        $yeartable->data[0]->cells[] = $emptycell;
        echo html_writer::table($yeartable);
    echo '</br>';
    echo '<form action="action_redir.php" method="post" name="form1" id="id_form1" >';
    echo '<input type="hidden" name="id" value="'.$course->id.'" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '';
        echo html_writer::label(get_string('infoautosearch', 'report_report_pstgu_deanery'), '');

        $compliancetable = new html_table();
        $compliancetable->attributes['class'] = 'controls';
        $compliancetable->cellspacing = 0;
        $compliancetable->data[] = new html_table_row();

        $selectlabelcell = new html_table_cell(html_writer::label('Учебный год в ИС Деканат&nbsp;&nbsp;', 'selecеisyear'));
        $selectlabelcell->attributes['class'] = 'left';
        //год в ИС либо выбранный либо текущий
        if(!isset($formdata->selecеisyear))
            $i = GetTempYearIndex($optionsDeanYear);
        else
            $i = $formdata->selecеisyear;
        $selectcell = new html_table_cell(html_writer::select($optionsDeanYear, 'selecеisyear', $i.'', null));
        $selectcell->attributes['class'] = 'left';

        $semestrlabelcell = new html_table_cell(html_writer::label('&nbsp;&nbsp;&nbsp;&nbsp;семестр&nbsp;&nbsp;', 'selectsemestr'));
        $semestrlabelcell->attributes['class'] = 'left';

        $options = array();
        for($i = 1; $i <= 12; $i++)
            if($i < 10)
                $options["0" . $i] = "0" . $i; 
            else
                $options[$i.''] = $i;
        $semestrselectcell = new html_table_cell();
        $semestrselectcell->attributes['class'] = 'left';
        if(!isset($formdata->selectsemestr))
            $sel = '0';
        else
            $sel = $formdata->selectsemestr;
        $semestrselectcell->text = html_writer::select($options, 'selectsemestr', $sel, null);

        $btncompliancecell = new html_table_cell();
        $btncompliancecell->attributes['class'] = 'right';
        $btnval = get_string('findcompliance', 'report_report_pstgu_deanery');        
        $btncompliancecell->text =  '<input '.$Enabled.' name="foundbutton" type="submit" for="id_selectform" formaction="'.$baseurl.'" id="id_foundbutton" value="'.$btnval.'"/>';

        $compliancetable->data[0]->cells[] = $selectlabelcell;
        $compliancetable->data[0]->cells[] = $selectcell;
        $compliancetable->data[0]->cells[] = $semestrlabelcell;
        $compliancetable->data[0]->cells[] = $semestrselectcell;
        $compliancetable->data[0]->cells[] = $btncompliancecell;
        $compliancetable->data[0]->cells[] = $emptycell;
        echo html_writer::table($compliancetable);

    echo '</br>';
    echo '';

        echo html_writer::label('3.Проверьте соответствие между группами, если требуется, то измените, и затем сохраните:', '');
        $grouptable = new html_table();
        $grouptable->attributes['class'] = 'controls';
        $grouptable->cellspacing = 0;
        $grouptable->data[] = new html_table_row();

        $cohortsdohead = new html_table_cell(html_writer::label('<b>Учебная группа в СДО</b>', ''));
        $cohortsdohead->attributes['class'] = 'left';
        $grouptable->data[0]->cells[] = $cohortsdohead;
        // добавляем пустую ячейку в качестве разделителя
        $grouptable->data[0]->cells[] = $emptycell;

        $isdeaneryhead = new html_table_cell(html_writer::label('<b>Группа в ИС Деканат</b>', ''));
        $isdeaneryhead->attributes['class'] = 'right';
        $grouptable->data[0]->cells[] = $isdeaneryhead;
        //массив выпадающих списков
        $groupdeaneryoptions = array();
        GetGropMenu($result, $groupdeaneryoptions, $course_ciph->cipher);
        $i = 1;
        foreach($result as $val)
        {
            $name = $val->name;
            //столбец с названиями групп
            $grouptable->data[] = new html_table_row();
            $cell1 = new html_table_cell($name);
            $cell1->attributes['class'] = 'left';
            setlocale(LC_ALL, 'ru_RU.utf8');
            $k = preg_split('/[^\w]+/u', $name);
            //$keys = array_keys($groupdeaneryoptions[$k[2]]);
            //определяем, что выбирать, либо ставим умолчание
            $s_is_gr = $val->idnumber;//$keys[0];
            if(isset($selectionsyear[$name]))
            {
                $s_is_gr = $selectionsyear[$name];
            }
            elseif(isset($ans))
            {
                if($ans == 'Worning')                
                { 
                    $s_is_gr = '';
                }
            }
            //столбец с выбором групп из ИС деканат
            $cell2 = new html_table_cell(html_writer::select($groupdeaneryoptions[$k[2]], 'groupdeanery['.$name.']', $s_is_gr));
            $cell2->attributes['class'] = 'right';
            $grouptable->data[$i]->cells[] = $cell1;
            $grouptable->data[$i]->cells[] = $emptycell;
            $grouptable->data[$i]->cells[] = $cell2;
            $i++;
        }
        echo html_writer::table($grouptable);
        $savebtntable = new html_table();
        $savebtntable->attributes['class'] = 'controls';
        $savebtntable->cellspacing = 0;
        $savebtntable->data[] = new html_table_row();
        $savebtntable->data[0]->cells[] = $emptycell;

        $savebtncell = new html_table_cell('<input '.$Enabled.' name="submitbutton" type="submit" for="id_selectform" formaction="'.$baseurl.'" id="id_submitbutton" value="Сохранить"/>');
        $savebtncell->attributes['class'] = 'right';
        $savebtntable->data[0]->cells[] = $emptycell;
        $savebtntable->data[0]->cells[] = $emptycell;
        $savebtntable->data[0]->cells[] = $emptycell;
        $savebtntable->data[0]->cells[] = $emptycell;
        $savebtntable->data[0]->cells[] = $savebtncell;
        echo html_writer::table($savebtntable);
    echo '</br>';
    echo '</form>';
}