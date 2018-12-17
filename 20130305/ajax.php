<?php

if (isset($_GET['f']))
{
    //Verbindung zur Datenbank
    connection();
    
    //Sucheinagabe
    if ($_GET['f'] == 1)
    {
        if (isset($_POST['val']) AND isset($_POST['lang']))
        {
            $val = $_POST['val'];
            $lang = $_POST['lang'];
            $inWords = splitRates($val);
            $outWords;
            foreach($inWords as $key => $word)
            {
                if ($word != "")
                {
                    $outWords[] = $word;
                }
            }
            
            $answer = getAnswer(getWordIds($outWords), $lang);
            
            echo json_encode(array($outWords, $answer)); 
        }
    }
    
    //Antwort Eingabe
    if ($_GET['f'] == 2)
    {
        if (isset($_POST['words']) AND isset($_POST['answer']) AND isset($_POST['lang']))
        {
            $words = $_POST['words'];
            $answer = $_POST['answer'];
            $lang = $_POST['lang'];
            
            mysql_query("INSERT INTO answers (value,lang) VALUES ('".$answer."',".$lang.")") or die (mysql_error());
            $answerId = mysql_insert_id();
            foreach ($words as $word)
            {
                wordToAnswer($word, $answerId, $lang);
            }
        }
        echo 1;
    }
    
    //Antwort bestätigen
    if ($_GET['f'] == 3)
    {
        if (isset($_POST['words']) AND isset($_POST['answerId']) AND isset($_POST['lang']))
        {
            $words = $_POST['words'];
            $answerId = $_POST['answerId'];
            $lang = $_POST['lang'];
            
            foreach ($words as $word)
            {
                wordToAnswer($word, $answerId, $lang);
            } 
        }
    }
    
    //Wort analysieren
    if ($_GET['f'] == 4)
    {
        if (isset($_POST['words']) AND isset($_POST['data']) AND isset($_POST['lang']))
        {
            $words = $_POST['words'];
            $data = $_POST['data'];
            $lang = $_POST['lang'];
            
            foreach ($data as $word)
            {
                $priority = 0;
                if ($word[1] == "true")
                {
                    $priority = 100;
                }
                //Wörter schreiben
                $wordId = setWord($word[0], $lang, $priority);
                //Synonym bearbeiten
                $syns = splitRates($word[2]);
                foreach ($syns as $syn)
                {
                    $meansId = setWord($syn, $lang, $priority);
                    wordMeans($wordId, $meansId);
                }
            }
        }
    }
    
}

function wordToAnswer($word, $answerId, $lang)
{
    $wordId = setWord($word, $lang);
    
    //Verbindung Wort Answer
    $result = mysql_query("SELECT id,uses FROM wordsForAnswer WHERE wordId = ".$wordId." AND answerId = ".$answerId) or die (mysql_error()); 
    //Wenn es die verknüpung bereits gibt
    $not = true;
    while($row = mysql_fetch_array($result))
    {
        $newUsing = $row['uses'] +1;
        mysql_query("UPDATE wordsForAnswer SET uses = '".$newUsing."' WHERE id = ".$row['id']) or die (mysql_error());
        $not = false;
    }
    //Wenn nicht
    if ($not)
    {
        mysql_query("INSERT INTO wordsForAnswer (wordId,answerId,uses) VALUES (".$wordId.",".$answerId.",1)") or die (mysql_error());
    }
}

function setWord($word, $lang, $priority = 50)
{
    //Wort in Kleinbuchstaben
    $word = strtolower($word);

    //Wort in Datenabnk schreiben
    $result = mysql_query("SELECT id,priority,uses FROM words WHERE value like '".$word."' AND lang = ".$lang) or die (mysql_error()); 
    //Wenn Wort bereits exestiert
    $wordId;
    while($row = mysql_fetch_array($result))
    {
        $newUsing = $row['uses'] +1;
        $newPriority = (($row['uses']*$row['priority'])+$priority)/$newUsing;
        mysql_query("UPDATE words SET uses = '".$newUsing."' AND priority = '".$newPriority."' WHERE id = ".$row['id']) or die (mysql_error());
        $wordId = $row['id'];
        break;
    }
    //Wenn nicht
    if (!isset($wordId))
    {
        mysql_query("INSERT INTO words (value,lang,priority,uses) VALUES ('".$word."',".$lang.",".$priority.",1)") or die (mysql_error());
        $wordId = mysql_insert_id();
    }
    return $wordId;
}

function getAnswer($words, $lang)
{
    
    $answerIds = array();
    
    foreach($words as $id)
    {
       $priority = 0;
       $result = mysql_query("SELECT priority FROM words WHERE id = ".$id) or die (mysql_error()); 
       while($row = mysql_fetch_array($result))
       {
           $priority = $row['priority'];
           break;
       }
       
       //Wenn es das Wort bereits gibt
       if (isset($id))
       {
            $result = mysql_query("SELECT answerId,uses FROM wordsForAnswer WHERE wordId = ".$id) or die (mysql_error()); 
            while($row = mysql_fetch_array($result))
            {
                if (isset($answerIds[$row['answerId']]))
                {
                    $answerIds[$row['answerId']] += $priority*$row['uses'];
                }
                else
                {
                    $answerIds[$row['answerId']] = $priority*$row['uses']; 
                }
            }
       }
    }
    //Sortieren der Anworten von hoch nach tief
    arsort($answerIds);
    
    $returnAnswers = array();
    
    foreach($answerIds as $key => $value)
    {
        $result = mysql_query("SELECT value FROM answers WHERE id = ".$key) or die (mysql_error()); 
        while($row = mysql_fetch_array($result))
        {
             $returnAnswers[] = array($row['value'],$key);
        }
    }
    
    return $returnAnswers;
}

function splitRates($rate)
{
    //Zeichen entfernen
    $signs = array("!", "?", ".", ",");
    $rate = str_replace($signs, "", $rate);
    return explode(" ", $rate);
}

function getWordIds($words)
{
    $wordIds = array();
    foreach ($words as $word)
    {
        $word = strtolower($word);
        
        $result = mysql_query("SELECT id FROM words WHERE value like '".$word."'") or die (mysql_error()); 
        while($row = mysql_fetch_array($result))
        {
            $wordIds[] = $row['id'];
            $resultMeans = mysql_query("SELECT word,means FROM wordMeansWord WHERE word = ".$row['id']." OR means = ".$row['id']) or die (mysql_error());
            while($rowMeans = mysql_fetch_array($resultMeans))
            {
                if ($rowMeans['word'] != $row['id'])
                {
                    $wordIds[] = $rowMeans['word'];
                }
                if ($rowMeans['means'] != $row['id'])
                {
                    $wordIds[] = $rowMeans['means'];
                }
            }
            break;
        }
    }
    return $wordIds;
}

function wordMeans($wordId, $meansId)
{
    //Überpüfen ob es die Beziehung bereits gibt
    $result = mysql_query("SELECT id,uses FROM wordMeansWord WHERE word = ".$wordId." AND means = ".$meansId) or die (mysql_error()); 
    $not = true;
    while($row = mysql_fetch_array($result))
    {
        $newUsing = $row['uses']+1;
        mysql_query("UPDATE wordMeansWord SET uses = '".$newUsing."' WHERE id = ".$row['id']) or die (mysql_error());
        $not = false;
        break;
    }
    if ($not)
    {
        mysql_query("INSERT INTO wordMeansWord (word, means, uses) VALUES (".$wordId.",".$meansId.",1)") or die (mysql_error());
    }
}

function connection()
{
    $mysqlhost="localhost"; // MySQL-Host angeben

    $mysqluser="root"; // MySQL-User angeben

    $mysqlpwd=""; // Passwort angeben

    $connection = mysql_connect($mysqlhost, $mysqluser, $mysqlpwd) or die ("Verbindungsversuch fehlgeschlagen");
    @mysql_select_db ("omni", $connection);  
}

?>
