<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <title>Omny [Beta]</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script language="JavaScript" type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script language="JavaScript" type="text/javascript" src="script.js"></script>
  </head>
  <body>
      <div id="topArea">
        <div id="searchInput">
            <span id="toolName">Omny</span>
            <input placeholder="Ihre Frage..." autofocus size="60" id="searchValue" onkeydown="if (event.keyCode == 13) { getInput(); return false; }"/>
            <input type="submit" name="enter" class="searchButton" onclick="getInput()" value="Los.."/>
            <div id="langArea">
            <span Id="userName"></span>
            Sprache: 
            <select>
                <option value="">Deutsch</option>
            </select>
            </div>   
        </div>
      </div>
      <div id="searchMenuArea">
      </div>
      <div id="main">
          <div id="settingArea">
          </div>
        <div id="searchAnswer">
        </div>
      </div>
      <div id="footer">
          <a href="mailto:vgiselbrecht@hotmail.com">Feedback</a> | <a href="http://www.gise.tk" target="_blank">Entwickler</a> |  <a class="link" onclick="popup('Valentin Giselbrecht<br />Unterköhler 156<br />6934 Sulzberg<br />Email: <a href=\'mailto:vgiselbrecht@hotmail.com\'>vgiselbrecht@hotmail.com</a>')">Impressum</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      </div>
      <div id="dialog-overlay"></div>  
      <div id="dialog-box">  
        <div class="dialog-content">  
            <div id="dialog-message"></div>  
            <a class="buttonDia link">Schließen</a>  
        </div>  
    </div>  
  </body>
</html>