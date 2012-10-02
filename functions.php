<?php

// Copyright (c) 2012 Remy van Elst
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

# per kWh
$NPPprice=0.22716;
# per kWh
$DPPprice=0.20556;
# per m3
$GASprice=0.64354;
# per m3
$H2Oprice=0.914;
# price symbol/name
$currency="EUR";


//http://www.tek-tips.com/viewthread.cfm?qid=1568788
function arrayFilter($arrHaystack, $arrFilter, $boolStrict = false)
{
    if (!is_array($arrFilter)) $arrFilter = array($arrFilter);
    if (!is_array($arrHaystack)) $arrHaystack = array($arrHaystack);

    foreach ($arrHaystack as $strHKey => $objHValue)
    {
        if (is_array($objHValue))
            $boolFound = arrayFilter($objHValue, $arrFilter, $boolStrict);
        else
        {
            $strHKey = strtolower($strHKey);
            $objHValue = strtolower($objHValue);
            foreach ($arrFilter as $strFKey => $objFValue)
            {
                $strFKey = strtolower($strFKey);
                $objFValue = strtolower($objFValue);
                                
                $boolMatch = (($strFKey == $strHKey) AND ($objFValue == $objHValue));
            
                if ($boolMatch == 1)
                    if ($boolStrict)
                        unset($arrFilter[$strFKey]);
                    else
                        $arrFilter = array();
                        
                if (count($arrFilter) == 0)
                    return true;
            }
        }
        
        if ($boolFound)
            $arrResult[$strHKey] = $objHValue;
    }

    return $arrResult;    
}


function makegraph($array,$shortcode,$color,$maxitems) {


    $arrFilter = array("type" => $shortcode);
    $json_a=arrayFilter($array, $arrFilter, true);

    ?>
        <div id="<?php echo $shortcode; ?>-chart">
            <div id="<?php echo $shortcode; ?>graph" style="width:500px;height:150px;"></div>
            <script type="text/javascript">
            $(function () {

                <?php
                $codeitems=1;
                $lastcode=null;

                $totalitems=count($json_a);
                $start_loop=0;
                if(floatval($totalitems) > floatval($maxitems)) {
                    $array_diff = floatval($totalitems) - floatval($maxitems);
                    $start_loop = $array_diff;
                }
                echo "var d2 = [";
                foreach (array_slice($json_a, $start_loop) as $item => $value) {
                        if($codeitems < $maxitems+1) {
                            $day=date("D",strtotime($value['date'])); 
                            if($codeitems > 1) {
                                $codemin =  floatval($value['content']) - floatval($lastcode);
                            } else {
                                $codemin = 0;
                            }
                            if ($codeitems >= 2) {
                                echo "[" . $codeitems. ", " . $codemin . "], ";
                            }

                            $codeitems+=1;
                            $lastcode=$value['content'];

                        }
                }
                echo "];";
                ?>  
                $.plot($("#<?php echo $shortcode; ?>graph"), [ d2 ], {colors: ['<?php echo $color; ?>']});
            });
            </script>
            <?php

            echo "</div>";
            unset($json_a);
            unset($totalitems);
}
# end makegraph function

function showitems($array,$name,$shortcode,$maxitems,$price) {

    global $currency;
    $arrFilter = array("type" => $shortcode);
    $json_a=arrayFilter($array, $arrFilter, true);
    $averageusage = array('');

    $codeitems=1;
            $lastcode=null;
                $totalitems=count($json_a);
                $start_loop=0;
                if(floatval($totalitems) > floatval($maxitems)) {
                    $array_diff = floatval($totalitems) - floatval($maxitems);
                    $start_loop = $array_diff;
                }
            foreach (array_slice($json_a, $start_loop) as $item => $value) {
                if ($value['type'] == $shortcode) { 
                    if($codeitems < $maxitems+1) {
                        if($codeitems > 1) {
                            $codemin =  floatval($value['content']) - floatval($lastcode);
                            $itemprice = $codemin * $price;
                            $averageusage[] = $codemin;
                            $doavg = 1;
                        } else {
                            $codemin = 0;
                            $itemprice = 0;
                        }
                        echo "<b>" . $codeitems . ":</b>"  . $value['date'] . ' - ' . $name . ' usage: ' . $value['content'];              
                        if ($itemprice != 0) {
                            echo ' - Difference with day before: ' . round($codemin,3) . '.';
                            echo " [". $currency.": ".round($itemprice,2)."]";
                        }
                        echo " [ <a href=\"action.php?id=" .$item. "&action=edit\"><span class=\"icon small darkgray\" data-icon=\"7\"></span></a>";
                        echo " <a href=\"action.php?id=" .$item. "&action=delete\"><span class=\"icon small darkgray\" data-icon=\"T\"></span></a> ] \n\r";
                        echo "<br />";
                        $codeitems+=1;
                        $lastcode=$value['content'];
                    }
                }   
            }
            if($doavg == 1) {
                echo "Average difference: ". round(calculate_average($averageusage),2);
            }
}
#end showitems function

#via http://www.mdj.us/web-development/php-programming/calculating-the-median-average-values-of-an-array-with-php/
# start median calculate function
function calculate_median($arr) {
    sort($arr);
    $count = count($arr); //total numbers in array
    $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
    if($count % 2) { // odd number, middle is the median
        $median = $arr[$middleval];
    } else { // even number, calculate avg of 2 medians
        $low = $arr[$middleval];
        $high = $arr[$middleval+1];
        $median = (($low+$high)/2);
    }
    return $median;
}
# end media calculate function

#via http://www.mdj.us/web-development/php-programming/calculating-the-median-average-values-of-an-array-with-php/
# start average calculate
function calculate_average($arr) {
    $count = count($arr); //total numbers in array
    foreach ($arr as $value) {
        $total = $total + $value; // total value of array numbers
    }
    $average = ($total/$count); // get average value
    return $average;
}
#end average calculate



# start showform function

function showinputform($actionpage) {

    $vandaag=date('m-d-Y');

    echo "<h3>Add Value</h3>\n";
    echo "<form name=\"edit\" action=\"". $actionpage ."\" method=\"GET\">\n";
    echo "<select name=\"type\">\n";
    echo "<option value=\"NPP\">Normal Price power</option>\n";
    echo "<option value=\"DPP\">Discount Price power</option>\n";
    echo "<option value=\"GAS\">Gas</option>\n";
    echo "<option value=\"H2O\">Water</option>\n";
    echo "</select>\n";
    echo "  <input name=\"content\" type=\"text\" placeholder=\"Value\" ></input>\n";
    echo "<input name=\"date\" type=\"text\" value=\"${vandaag}\"></input>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"add\"></input>\n";
    echo "&nbsp; &nbsp; ";
    echo "<input type=\"submit\" name=\"submit\" value=\"Add Item\"></input>\n";
    echo "</form>\n";
}


?>