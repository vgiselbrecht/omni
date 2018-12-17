<?php
header("Content-Type: text/html; charset=utf-8");
session_start();
require_once 'connection.php';

if (isset($_GET['f']))
{
    //Verbindung zur Datenbank
    connection();
    
    //Sucheinagabe
    if ($_GET['f'] == 1)
    {
        if (isset($_POST['val']) AND isset($_POST['app']) AND isset($_POST['lang']) AND is_numeric($_POST['lang']))
        {
            $start = microtime(true);
            $val = addslashes($_POST['val']);
            $lang = $_POST['lang'];
            //Antworten pro Seite
            $app = $_POST['app'];
            $inWords = splitRates($val);
            $outWords;
            foreach($inWords as $key => $word)
            {
                if ($word != "")
                {
                    $outWords[] = $word;
                }
            }
            if (is_numeric($app[0]) AND is_numeric($app[0]))
            {
                $answer = getAnswer(getWordIds($outWords,$lang), $lang, $app);
            }
            $end = microtime(true);
            echo json_encode(array($outWords, $answer[0], $answer[1],round($end-$start,3))); 
        }
    }
    
    //Antwort Eingabe
    if ($_GET['f'] == 2 && $_SESSION['login'])
    {
        if (isset($_POST['words']) AND isset($_POST['answer']) AND isset($_POST['lang']) AND is_numeric($_POST['lang']))
        {
            $words = $_POST['words'];
            $answer = addslashes($_POST['answer']);
            $lang = $_POST['lang'];
            
            mysql_query("INSERT INTO answers (value,lang) VALUES ('".$answer."',".$lang.")") or die (mysql_error());
            $answerId = mysql_insert_id();
            setRates($words, $answerId, $lang);
        }
        echo 1;
    }
    
    //Antwort bestätigen
    if ($_GET['f'] == 3 && $_SESSION['login'])
    {
        if (isset($_POST['words']) AND isset($_POST['answerId']) AND isset($_POST['lang']) AND is_numeric($_POST['answerId']) AND is_numeric($_POST['lang']))
        {
            $words = $_POST['words'];
            $answerId = $_POST['answerId'];
            $lang = $_POST['lang'];
            setRates($words, $answerId, $lang);
        }
    }
    
    //Wort analysieren
    if ($_GET['f'] == 4 && $_SESSION['login'])
    {
        if (isset($_POST['words']) AND isset($_POST['data']) AND isset($_POST['lang']) AND is_numeric($_POST['lang']))
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
                    if ($syn)
                    {
                        $meansId = setWord($syn, $lang, $priority);
                        wordMeans($wordId, $meansId);
                    }
                }
            }
        }
    }
    
    //Login
    if ($_GET['f'] == 5)
    {
        if (isset($_POST['email']) AND isset($_POST['password']))
        {
            $email = addslashes($_POST['email']);
            $password = addslashes($_POST['password']);
            echo json_encode(login($email,$password));
        }
    }
    
    //Registrieren
    if ($_GET['f'] == 6)
    {
        if (isset($_POST['email']) AND isset($_POST['vorname']) AND isset($_POST['nachname']) AND isset($_POST['password']))
        {
            $email = addslashes($_POST['email']);
            $vorname = addslashes($_POST['vorname']);
            $nachname = addslashes($_POST['nachname']);
            $password = addslashes($_POST['password']);
            echo json_encode(regi($email,$vorname,$nachname,$password));
        }
    }
    
    //Logout
    if ($_GET['f'] == 7 && $_SESSION['login'])
    {
        $_SESSION['login'] = false;
        $_SESSION['vn'] = '';
        $_SESSION['nn'] = '';
        $_SESSION['email'] = '';
        echo 1;
    }
    
    //Status
    if ($_GET['f'] == 8)
    {
        if (isset($_SESSION['login']))
        {
            echo json_encode(array($_SESSION['login']."",$_SESSION['vn'],$_SESSION['nn'],$_SESSION['email']));
        }
        else
        {
            echo json_encode(array(0,0,0,0));
        }
    }
    
    //UserDataChange
    if ($_GET['f'] == 9 && $_SESSION['login'])
    {
        if (isset($_POST['email']) AND isset($_POST['vorname']) AND isset($_POST['nachname']))
        {
            $email = addslashes($_POST['email']);
            $vorname = addslashes($_POST['vorname']);
            $nachname = addslashes($_POST['nachname']);
            echo json_encode(changeUserData($email,$vorname,$nachname));
        }
    }
    
    //UserDataChange
    if ($_GET['f'] == 10 && $_SESSION['login'])
    {
        if (isset($_POST['opw']) AND isset($_POST['password']))
        {
            $opw = addslashes($_POST['opw']);
            $password = addslashes($_POST['password']);
            echo json_encode(changePassword($opw,$password));
        }
    }
}

function login($email, $password)
{
    $result = mysql_query("SELECT id,vn,nn,email FROM users WHERE email like '".$email."' AND pw = '".md5($password)."'") or die (mysql_error()); 
    while($row = mysql_fetch_array($result))
    {
        return setLoginValue($row['email'], $row['vn'], $row['nn']);
        break;
    }
    return 0;
}

function regi($email, $vorname, $nachname, $password)
{
    
    $result = mysql_query("SELECT id FROM users WHERE email like '".$email."'")or die (mysql_error()); 
    if (mysql_num_rows($result) == 0)
    {
        mysql_query("INSERT INTO users (email,vn,nn,pw) VALUES ('".$email."','".$vorname."','".$nachname."','".md5($password)."')") or die (mysql_error());
        return setLoginValue($email, $vorname, $nachname);
    }
}

function changeUserData($email,$vorname,$nachname)
{
    $result = mysql_query("SELECT id FROM users WHERE email like '".$email."'")or die (mysql_error()); 
    if (mysql_num_rows($result) == 0 || $email == $_SESSION['email'])
    {
        mysql_query("UPDATE users SET email='".$email."',vn='".$vorname."',nn='".$nachname."' WHERE email like '".$_SESSION['email']."'") or die (mysql_error());
        return setLoginValue($email, $vorname, $nachname);
    }
}

function changePassword($opw,$password)
{
    $result = mysql_query("SELECT id FROM users WHERE email like '".$_SESSION['email']."' AND pw = '".md5($opw)."'")or die (mysql_error()); 
    if (mysql_num_rows($result) == 1)
    {
        mysql_query("UPDATE users SET pw='".md5($password)."' WHERE email like '".$_SESSION['email']."'") or die (mysql_error());
        return setLoginValue($_SESSION['email'], $_SESSION['vn'], $_SESSION['nn']);
    }
}

function setLoginValue($email, $vorname, $nachname){
    $_SESSION['login'] = true;
    $_SESSION['vn'] = $vorname;
    $_SESSION['nn'] = $nachname;
    $_SESSION['email'] = $email;
    return array($vorname,$nachname,$email);
}

/*
 * Das Speicher von Sätzen
 */
function setRates($words, $answerId, $lang)
{
   //Als Satz schreiben
    foreach ($words as $word)
    {
        wordToAnswer($word, $answerId, $lang);
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
        mysql_query("UPDATE wordsForAnswer SET uses = ".$newUsing." WHERE id = ".$row['id']) or die (mysql_error());
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
    $word = strtolower(addslashes($word));
    //Überpürfen ob word nicht leer
    if ($word)
    {
        //Wort in Datenabnk schreiben
        $result = mysql_query("SELECT id,priority,uses FROM words WHERE value like '".$word."' AND lang = ".$lang) or die (mysql_error()); 
        //Wenn Wort bereits exestiert
        $wordId;
        while($row = mysql_fetch_array($result))
        {
            $newUsing = $row['uses'] + 1;
            $newPriority = (($row['uses']*$row['priority'])+$priority)/$newUsing;
            mysql_query("UPDATE words SET uses = ".$newUsing." , priority = ".$newPriority." WHERE id = ".$row['id']) or die (mysql_error());
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
}

function getAnswer($words, $lang, $app)
{
    $answerIds = array();
    
    foreach($words as $wordGroup)
    {
        $answerWordIds = array();
        foreach($wordGroup as $id)
        {
            $priority = 0;
            $priority = mysql_result(mysql_query("SELECT priority FROM words WHERE id = ".$id), 0)or die (mysql_error()); 

            //Wenn es das Wort bereits gibt
            if (isset($id))
            {
                 $result = mysql_query("SELECT answerId,uses FROM wordsForAnswer WHERE wordId = ".$id) or die (mysql_error()); 
                 while($row = mysql_fetch_array($result))
                 {
                     if (isset($answerWordIds[$row['answerId']]))
                     {
                         $answerWordIds[$row['answerId']] += $priority*$row['uses'];
                     }
                     else
                     {
                         $answerWordIds[$row['answerId']] = $priority*$row['uses']; 
                     }
                 }
            }
        }
        //Überprüfen ob es ein Wort vorher schon  die gleiche antwort hat
        foreach ($answerWordIds as $key => $answerWordId)
        {
            if (isset($answerIds[$key]))
            {
                $answerIds[$key] = $answerIds[$key] * $answerWordId;
            }
            else
            {
                $answerIds[$key] = $answerWordId;
            }
        }
    }
    
    foreach($answerIds as $key => $answerId)
    {
        $count = mysql_result(mysql_query("SELECT count(id) FROM wordsForAnswer WHERE answerId = ".$key), 0)or die (mysql_error()); 
        $answerIds[$key] = $answerId/$count;
    }
    
    
    //Sortieren der Anworten von hoch nach tief
    arsort($answerIds);
    
    $answerCount = count($answerIds);
    $returnAnswers = array();
    $count = 0;
    foreach($answerIds as $key => $value)
    {
        $count++;
        if ($count >= $app[0])
        {
            $result = mysql_result(mysql_query("SELECT value FROM answers WHERE id = ".$key), 0)or die (mysql_error()); 
            $returnAnswers[] = array($result,$key,round($value));
        }
        if ($count == $app[1])
        {
            break;
        }
    }
    
    return array($returnAnswers,$answerCount,);
}

function splitRates($rate)
{
    //Zeichen entfernen
    $signs = array("!", "?", ".", ",");
    $rate = str_replace($signs, "", $rate);
    return explode(" ", $rate);
}

/*
 * Gibt alle id aller Wörter zurück die eingeben wurden oder synonyme deren sind wenn $withSyn != false
 */
function getWordIds($words, $lang, $withSyn = true)
{
    $wordIds = array();
    foreach ($words as $word)
    {
        $word = strtolower(addslashes($word));
        $wordIdsSpec = array();
        
        $result = mysql_query("SELECT id FROM words WHERE value like '".$word."' AND lang = ".$lang) or die (mysql_error()); 
        while($row = mysql_fetch_array($result))
        {
            $wordIdsSpec[] = $row['id'];
            
            //Muss noch Fertig entwickelt werden
            /*if ($withSyn)
            {
                $resultMeans = mysql_query("SELECT word,means FROM wordMeansWord WHERE word = ".$row['id']." OR means = ".$row['id']."ORDER BY uses DESC") or die (mysql_error());
                while($rowMeans = mysql_fetch_array($resultMeans))
                {
                    if ($rowMeans['word'] != $row['id'])
                    {
                        $wordIdsSpec[] = $rowMeans['word'];
                    }
                    if ($rowMeans['means'] != $row['id'])
                    {
                        $wordIdsSpec[]= $rowMeans['means'];
                    }
                }
            }*/
            
            $wordIds[] = $wordIdsSpec;
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
?>
