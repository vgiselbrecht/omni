
var words;
var answers;
var lang = 1;

function search()
{
    var val = $("#searchValue").val();
    if (val != "")
    {
        jQuery.ajax({
        url: "ajax.php?f=1",
        type: "POST",
        data: {val: val, lang: lang},
        dataType: "json",
        success: function(result) {
                words = result[0];
                answers = result[1];
                writeSettings();
                writeAnswers();
            }
        });
    }
}

function writeSettings()
{
    var searchSettings = $("#searchSettings");
    var print = "<table><tr><td>Wort:</td>";
   
    for (var i = 0; i < words.length; i++)
    {
        print += '<td id="w'+i+'">' + words[i] +'</td>';
    }
    
    print += "</tr><tr><td>Wichtig:</td>"
    
    for (var i = 0; i < words.length; i++)
    {
        print += '<td><input id="c'+i+'" type="checkbox" /></td>';
    }
    
    print += "</tr><tr><td>Nennform:</td>"
    
    for (var i = 0; i < words.length; i++)
    {
        print += '<td><input id="s'+i+'" type="text" size="10" /></td>';
    }
    
    print += '<td><input type="submit" value="Speichern" onclick="setSettings()"/></td></tr></table>';
    
    searchSettings.html(print);
}

function writeAnswers()
{
    var searchAnswer = $("#searchAnswer");
    var print = '';
    print += '<div id="newAnswer"><input type="submit" value="Neue Antwort" onclick="newAnswer()"/></div>';
    var haveAnswers = false;
    for (var i = 0; i < answers.length; i++)
    {
        print += '<table><tr><td>'+answers[i][0]+'</td><td><input type="submit" value="Richtig" onclick="answerIsRight('+answers[i][1]+')"/></td></tr></table>';
        
        haveAnswers = true;
    }
    if (!haveAnswers)
    {
        print += "Keine Antwort gefunden!";
    }
    searchAnswer.html(print);
}

function newAnswer()
{
    var newAnswer = $("#newAnswer");
    var print = '';
    print += '<textarea id="newAnswerInpute"></textarea><br /><input type="submit" value="Speicher" onclick="newAnswerSave()"/>';
    newAnswer.html(print);
}

function newAnswerSave()
{
    var newAnswerInpute = $("#newAnswerInpute");
    var val = newAnswerInpute.val();
    if (val != "")
    {
        jQuery.ajax({
        url: "ajax.php?f=2",
        type: "POST",
        data: {words: words, answer: val, lang: lang},
        dataType: "json",
        success: function(result) {
                var newAnswer = $("#newAnswer");
                if(result == 1){
                    newAnswer.html("Ihe Antwort:<br />"+val);
                }
                else
                {
                    newAnswer.html("Es ist ein Fehler aufgetreten!");
                }
                search();
            }
        });
    }
}

function answerIsRight(answerId)
{
    jQuery.ajax({
        url: "ajax.php?f=3",
        type: "POST",
        data: {words: words, answerId: answerId, lang: lang},
        dataType: "json",
        success: function(result) {
                
            }
        });
}

function setSettings()
{
    var i = 0;
    var data = [];
    do{
        if ($("#w"+i).length > 0)
        {
            var check = false;
            if ($("#c"+i).is(":checked"))
            {
                check = true;
            }
            data.push([$("#w"+i).html(),check,$("#s"+i).val()]);
        }
        else
        {
            break;
        }
        i++;    
    }while(true);
    jQuery.ajax({
        url: "ajax.php?f=4",
        type: "POST",
        data: {words: words, data: data, lang: lang},
        dataType: "json",
        success: function(result) {
                
            }
     });
    
}

