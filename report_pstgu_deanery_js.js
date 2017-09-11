/**
 * Делает неактивным выпадающий список с группами в зависимости от выбранного действия
 * 
 * @returns {void}
 */
function report_pstgu_deanery_disabled_if()
{
    var form = document.forms['participantsform'];
    if (form['actselect'].value == 3 || form['actselect'].value == 4)
    {
        form['cohortmenu'].disabled = false;
    }
    else
    {
        form['cohortmenu'].disabled = true;
    }
    
}
/**
 * Показывает диалог перед выполнением удаления или исключения
 * 
 * @param {event} e
 * @returns {void}
 */

function report_pstgu_deanery_submit_dialog(e)
{
    var form = document.forms['participantsform'];
    if(!e)
    {
        e = window.event;
    }
    if (form['actselect'].value == 1)
    {
        x = confirm('Вы действиетельно хотите удалить абитуриентов и заявления из ИС ПК?');
        if (x == false) 
        {
          //Если пользователь нажал Отмена, то отменяем событие
           e.preventDefault()
           return;
        }        
    }
}
