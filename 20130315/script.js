
var words;
var answers;
var lang = 1;
var answerPerPage = 20;
var actStartAnswer;
var answerCount;
var actPage = 1;
var inputeValue = '';
var time;
var setAnswer = false;
var loggedIn = false;
var vn = '';
var nn = '';
var email = '';

$(document).ready(function () {  
    
    //Dialog
    $('a.buttonDia').click(function () {       
        $('#dialog-overlay, #dialog-box').hide();         
        return false;  
    });  
    $(window).resize(function () {  
        if (!$('#dialog-box').is(':hidden')) popup();         
    });  
    
    //Login status
    $.getJSON('ajax.php?f=8', function(result) {
        if (result[0] == '1')
        {
            loggedIn = true;
            vn = result[1];
            nn = result[2];
            email = result[3];
            writeUserName();
        }
        //Anker String auslesen
        if(window.location.hash) {
            inputeValue = decodeURI(window.location.hash).substr(1);
            $("#searchValue").val(inputeValue);
            searchStart(1);
        }
    });
});  

function getInput()
{
    inputeValue = $("#searchValue").val();
    setAnswer = false;
    if (inputeValue != "")
    {
        window.location = '#'+encodeURI(inputeValue);
        searchStart(1);
        writeLoading();
    }
}

function searchStart(start)
{
    actStartAnswer = start;
    clearSettingArea();
    var end = start + answerPerPage - 1;
    if (inputeValue != "")
    {
        jQuery.ajax({
        url: "ajax.php?f=1",
        type: "POST",
        data: {val: inputeValue, lang: lang, app : [start,end]},
        dataType: "json",
        success: function(result) {
                words = result[0];
                answers = result[1];
                answerCount = result[2];
                time = result[3];
                actPage = (Math.floor(actStartAnswer/answerPerPage)+1)
                writeMenu();
                writeAnswers();
            }
        });
    }
}

function writeMenu()
{
    var searchMenuArea = $("#searchMenuArea");
    if (loggedIn)
    {
        var print = '<div id="searchMenu"><a onclick="writeSettings()" class="menuPoint menuPointLeft link">Einstellungen</a><a class="menuPoint link" onclick="newAnswer()">Neue Antwort</a><a class="menuPoint link" onclick="logout()">Logout</a></div>';
    }
    else
    {
        var print = '<div id="searchMenu"><a onclick="writeLoginScreen()" class="menuPoint menuPointLeft link">Login</a></div>';
    }
    searchMenuArea.html(print); 
}

function writeLoading()
{
    var searchAnswer = $("#searchAnswer");
    var print = 'Wird geladen...';
    searchAnswer.html(print); 
}

function writeSettings()
{
    var settingArea = $("#settingArea");
    var print = '<table class="settingTable"><tr><td>Wort:</td>';
   
    for (var i = 0; i < words.length; i++)
    {
        print += '<td id="w'+i+'">' + words[i] +'</td>';
    }
    
    print += '</tr><tr><td><span title="Gehört das Wort zu den Wichtigen in ihrem Satz?">Wichtig:</span></td>';
    
    for (var i = 0; i < words.length; i++)
    {
        print += '<td><input id="c'+i+'" type="checkbox" /></td>';
    }
    
    print += '</tr><tr><td><span title="Was ist die Nennform bzw. ein anderes Wort für Ihre Eingabe? Hier können auch häufige Rechtschreibfehler eingegeben werden!">Nennform:</span></td>';
    
    for (var i = 0; i < words.length; i++)
    {
        print += '<td><input id="s'+i+'" type="text" class="inputText" size="10" /></td>';
    }
    
    print += '</tr></table>';
    print += '<input class="button" type="submit" value="Speichern" onclick="setSettings()"/>  <input class="button" type="submit" value="Abbrechen" onclick="clearSettingArea()"/>';
    
    settingArea.html(print);
}

function writeAnswers()
{
    var searchAnswer = $("#searchAnswer");
    var print = '';
    if (answers.length == 0)
    {
        print += '<div class="noResult">Keine Antwort gefunden!</div>';
    }
    else
    {
        print += '<div class="resultCount">'+answerCount+' Treffer in '+time+' Sekunden zu "'+inputeValue+'"</div>';
        print += '<table id="answerTable">';
        for (var i = 0; i < answers.length; i++)
        {
            print += '<tr class="answerBlock';
            if (i == 0 && actPage == 1)
            {
                print += ' topAnswer';
            }
            print += '"><td title="Priority: '+answers[i][2]+'" class="answerLeft">'+answers[i][0]+'</td>';
            if (loggedIn)
            {
                print += '<td class="answerRight"><input type="submit" class="button" value="Korrekt" onclick="answerIsRight('+answers[i][1]+')"/></td>';
            }
            print += '</tr>';
        }
        print += "</table>";
        print += makePaging();
    }
    searchAnswer.html(print);
}

function newAnswer()
{
    var settingArea = $("#settingArea");
    var print = '';
    print += '<textarea id="newAnswerInpute"></textarea><br /><input type="submit" value="Speicher" class="button" onclick="newAnswerSave()"/>  <input type="submit" value="Abbrechen" class="button" onclick="clearSettingArea()"/>';
    settingArea.html(print);
}

function newAnswerSave()
{
    if (!setAnswer)
    {
        setAnswer = true;
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
                    searchStart(actStartAnswer);
                }
            });
        }
    }
    clearSettingArea();
}

function clearSettingArea()
{
    var settingArea = $("#settingArea");
    var print = '';
    print += '';
    settingArea.html(print);
}

function answerIsRight(answerId)
{
    if (!setAnswer)
    {
        setAnswer = true;
        jQuery.ajax({
            url: "ajax.php?f=3",
            type: "POST",
            data: {words: words, answerId: answerId, lang: lang},
            dataType: "json",
            success: function(result) {

                }
            });
    }
}

function  makePaging()
{
    var print = '<div id="pagingArea">';
    if (actStartAnswer > 1)
    {
        print += '<a class="link" onclick="searchStart(actStartAnswer - answerPerPage);">Zurück</a> | ';
    }
    else
    {
         print += 'Zurück | ';
    }
    print += 'Seite '+actPage;
    if (actStartAnswer + answerPerPage <= answerCount)
    {
        print += ' | <a class="link" onclick="searchStart(actStartAnswer + answerPerPage);">Weiter</a>';
    }
    else
    {
        print += ' | Weiter';
    }
    print += '</div>';
    return print;
}

function setSettings()
{
    var data = [];
    for(var i = 0; i < words.length; i++)
    {
        var check = false;
        if ($("#c"+i).is(":checked"))
        {
            check = true;
        }
        data.push([$("#w"+i).html(),check,$("#s"+i).val()]); 
    }
    jQuery.ajax({
        url: "ajax.php?f=4",
        type: "POST",
        data: {words: words, data: data, lang: lang},
        dataType: "json",
        success: function(result) {
                
            }
     });
     clearSettingArea();
}

function writeLoginScreen()
{
    var msg = '<div class="loginLabel">Anmelden</div><table class="loTa">';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vaEm"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Email:</td><td class="liRe"><input name="email" spellcheck="false" autofocus id="loEm" class="loginField inputText" type="text" /></td><tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vaPa"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Passwort:</td><td class="liRe"><input id="loPa" class="loginField inputText" type="password" onkeydown="if (event.keyCode == 13) { login(); return false; }" /></td><tr>';
    msg += '</table><div id="loginError"></div><input class="loginButton button" style="font-size:17px" onclick="login()" type="submit" value="Anmelden"/>';
    msg += '<br /><a class="link" onclick="writeRegiScreen()">Noch kein Konto</a>';
    popup(msg);
}

function login()
{
    $('.wrongInput').hide();
    var em = $("#loEm").val();
    var password = $("#loPa").val();
    if (em == "")
    {
        $("#vaEm").append(' <span class="wrongInput">Eingabe Benötigt</span>');
    }
    else if (!em.isEmail())
    {
        $("#vaEm").append(' <span class="wrongInput">Keine Gültige Eingabe</span>');
    }
    if (password == "")
    {
        $("#vaPa").append(' <span class="wrongInput">Eingabe Benötigt</span>');
    }
    if (em != "" && password != "" && em.isEmail())
    {
        jQuery.ajax({
        url: "ajax.php?f=5",
        type: "POST",
        data: {email: em, password: password},
        dataType: "json",
        success: function(result) {
                if (result)
                {
                    setLogin(result);
                }
                else
                {
                    $("#loginError").append(' <span class="wrongInput">Falsche Zugangsdaten</span>');
                }
            }
        });
    }
}

function writeRegiScreen(){
    var msg = '<div class="loginLabel">Registrieren</div><table class="loTa">';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veEm"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Email:</td><td class="liRe"><input name="email" autofocus id="reEm" class="loginField inputText" type="text" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veVn"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Vorname:</td><td class="liRe"><input id="reVn" class="loginField inputText" type="text" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veNn"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Nachname:</td><td class="liRe"><input id="reNn" class="loginField inputText" type="text" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vePa"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Passwort:</td><td class="liRe"><input id="rePa" class="loginField inputText" type="password" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vePaw"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Passwort bestätigen:</td><td class="liRe"><input id="rePaw" class="loginField inputText" type="password" /></td></tr>';
    msg += '</table><div id="loginError"></div><input class="loginButton button" style="font-size:17px" onclick="regi()" type="submit" value="Registrieren"/>';
    msg += '<br /><a class="link" onclick="writeLoginScreen()">Zur Anmelde Seite</a>'; 
    popup(msg);
}

function regi()
{
    $('.wrongInput').hide();
    var em = $("#reEm").val();
    var vn = $("#reVn").val();
    var nn = $("#reNn").val();
    var pw = $("#rePa").val();
    var pww = $("#rePaw").val();
    var canReg = true;
    if (em == "")
    {
        $("#veEm").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    else if (!em.isEmail())
    {
        $("#veEm").append(' <span class="wrongInput">Keine Gültige Eingabe</span>');
        canReg = false;
    }
     if (vn == "")
    {
        $("#veVn").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
     if (nn == "")
    {
        $("#veNn").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
     if (pw == "")
    {
        $("#vePa").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (pww == "")
    {
        $("#vePaw").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (pww != pw)
    {
        $("#vePaw").append(' <span class="wrongInput">Passwörter müssen gleich sein!</span>');
        canReg = false;
    }
    if (canReg)
    {
        jQuery.ajax({
        url: "ajax.php?f=6",
        type: "POST",
        data: {email: em, vorname: vn, nachname: nn, password: pw},
        dataType: "json",
        success: function(result) {
                if (result)
                {
                    setLogin(result);
                }
                else
                {
                    $("#loginError").append(' <span class="wrongInput">Email existiert bereits!</span>');
                }
            }
        });
    }
}

function writeChangeScreen(){
    var msg = '<div class="loginLabel">Benutzerdaten ändern</div><table class="loTa">';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veEm"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Email:</td><td class="liRe"><input value='+email+' id="reEm" class="loginField inputText" type="text" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veVn"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Vorname:</td><td class="liRe"><input value='+vn+' id="reVn" class="loginField inputText" type="text" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="veNn"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Nachname:</td><td class="liRe"><input value='+nn+' id="reNn" class="loginField inputText" type="text" /></td></tr>';
    msg += '</table><div id="loginError"></div><input class="loginButton button" style="font-size:17px" onclick="userDataChange()" type="submit" value="Speichern"/>';
    msg += '<br /><a class="link" onclick="changePasswordScreen()">Passwort ändern</a>';
    popup(msg);
}

function userDataChange()
{
    $('.wrongInput').hide();
    var em = $("#reEm").val();
    var vn = $("#reVn").val();
    var nn = $("#reNn").val();
    var canReg = true;
    if (em == "" )
    {
        $("#veEm").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    else if (!em.isEmail())
    {
        $("#veEm").append(' <span class="wrongInput">Keine Gültige Eingabe</span>');
        canReg = false;
    }
    if (vn == "")
    {
        $("#veVn").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
     if (nn == "")
    {
        $("#veNn").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (canReg)
    {
        jQuery.ajax({
        url: "ajax.php?f=9",
        type: "POST",
        data: {email: em, vorname: vn, nachname: nn},
        dataType: "json",
        success: function(result) {
                if (result)
                {
                    setLogin(result);
                }
                else
                {
                    $("#loginError").append(' <span class="wrongInput">Email existiert bereits!</span>');
                }
            }
        });
    }
}

function changePasswordScreen(){
    var msg = '<div class="loginLabel">Passwort ändern</div><table class="loTa">';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vePao"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Altest Passwort:</td><td class="liRe"><input id="rePao" class="loginField inputText" type="password" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vePa"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Neues Passwort:</td><td class="liRe"><input id="rePa" class="loginField inputText" type="password" /></td></tr>';
    msg += '<tr class="liCo errorTr"><td class="liLe"></td><td class="liRe"><div id="vePaw"></div></td></tr>';
    msg += '<tr class="liCo"><td class="liLe">Neues Passwort bestätigen:</td><td class="liRe"><input id="rePaw" class="loginField inputText" type="password" /></td></tr>';
    msg += '</table><div id="loginError"></div><input class="loginButton button" style="font-size:17px" onclick="changePassword()" type="submit" value="Speichern"/>';
    msg += '<br /><a class="link" onclick="writeChangeScreen()">Benutzerdaten ändern</a>';
    popup(msg);
}

function changePassword()
{
    $('.wrongInput').hide();
    var opw = $("#rePao").val();
    var pw = $("#rePa").val();
    var pww = $("#rePaw").val();
    var canReg = true;
    if (opw == "" )
    {
        $("#vePao").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (pw == "" )
    {
        $("#vePa").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (pww == "" )
    {
        $("#vePaw").append(' <span class="wrongInput">Eingabe Benötigt</span>');
        canReg = false;
    }
    if (pw != pww)
    {
        $("#vePaw").append(' <span class="wrongInput">Passwörter müssen gleich sein!</span>');
        canReg = false;
    }
    if (canReg)
    {
        jQuery.ajax({
        url: "ajax.php?f=10",
        type: "POST",
        data: {opw: opw, password: pw},
        dataType: "json",
        success: function(result) {
                if (result)
                {
                    setLogin(result);
                }
                else
                {
                    $("#loginError").append(' <span class="wrongInput">Falsches Passwort!</span>');
                }
            }
        });
    }
}

function setLogin(result)
{
    loggedIn = true;
    vn = result[0];
    nn = result[1];
    email = result[2];
    writeUserName();
    searchStart(actStartAnswer);
    $('#dialog-overlay, #dialog-box').hide(); 
}

function writeUserName(){
    $('#userName').html('<a onclick="writeChangeScreen()" class="link">'+vn+' '+nn+'</a>  |  '); 
}

function deleteUserName(){
    $('#userName').html(""); 
}

function logout()
{
    jQuery.ajax({
        url: "ajax.php?f=7",
        type: "POST",
        data: {},
        dataType: "json",
        success: function(result) {
                loggedIn = false;
                vn = '';
                nn = '';
                email = '';
                searchStart(actStartAnswer);
                deleteUserName();
            }
        });
}

//Popup dialog  
function popup(message) {  
          
    // get the screen height and width    
    var maskHeight = $(document).height();    
    var maskWidth = $(window).width();  
      
    // calculate the values for center alignment  
    if (maskHeight > $('#dialog-box').height())
    {
        var dialogTop =  (maskHeight - $('#dialog-box').height())/6;  
    }
    else
    {
        var dialogTop = 15;
    }
    var dialogLeft = (maskWidth/2) - ($('#dialog-box').width()/2);   
      
    // assign values to the overlay and dialog box  
    $('#dialog-overlay').css({height:maskHeight, width:maskWidth}).show();  
    $('#dialog-box').css({top:dialogTop, left:dialogLeft}).show();  
      
    // display the message  
    $('#dialog-message').html(message);  
              
} 

String.prototype.isEmail = function () {
  var validmailregex = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.([a-z][a-z]+)|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i
  return validmailregex.test(this);
}