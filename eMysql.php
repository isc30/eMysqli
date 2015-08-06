<?php
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Extended Mysqli (eMysqli) v1.0.1
    //  https://github.com/isc30/eMysqli
    //  By: isc30 -> ivansanzcarasa@gmail.com
    //
    //  Extended mysqli class that allows calling PROCEDURES, FUNCTIONS and VIEWS
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Usage:
    //
    //      - Get eMysqli object:
    //
    //          · $eMysqli = new eMysqli($host, $username, $password, $database);
    //              or
    //          · $eMysqli = getMysqlConnection();
    //
    //      - Calling a procedure:
    //
    //          · $eMysqli->callProcedure( 'PROCEDURE_NAME*', [INPUT], [OUTPUT] );
    //
    //          · Example:
    //
    //              · $result = $eMysqli->callProcedure('prTestLogin', [$email, $password], ['@ok', '@userId']);
    //              · $result == Array (
    //                      [pr] => ( )
    //                      [out] => ( [@ok] => true, [@userId] => 23142 )
    //                )
    // 
    //      - Calling a function:
    //
    //          · $eMysqli->callFunction( 'FUNCTION_NAME*', [INPUT] );
    //
    //          · Example:
    //
    //              · $result = $eMysqli->callFunction('fuGetSum', [26, 57]);
    //              · $result == 83
    // 
    //      - Calling a view:
    //
    //          · $eMysqli->callView( 'VIEW_NAME*' );
    //
    //          · Example:
    //
    //              · $result = $eMysqli->callView('viShowUsers');
    //              · $result == Array (
    //                      [0] => (
    //                          [id] => 1,
    //                          [username] => 'Paco'
    //                      )
    //                      [1] => (
    //                          [id] => 2,
    //                          [username] => 'Juan'
    //                      )
    //                )
    //
    //      - Getting an HTML output:
    //
    //          · $eMysqli->getHTML( [CALL_OUTPUT_ARRAY*], [TABLE_ATTRIBUTES], 'TABLE_NAME' );
    //
    //          · Example:
    //
    //              · $htmlCode = $eMysqli->getHTML($result);
    //              · $htmlCode = $eMysqli->getHTML($result, ['style' => 'background-color: orange;']);
    //              · $htmlCode = $eMysqli->getHTML($result, [], 'Users');
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // Include guard (C++ like)
    if(!defined('eMysqli')){
        define('eMysqli', true);
        
        // Optional function to get connection object :)
        function getMysqlConnection(){
            
            $host = "localhost";
            $username = "isc30";
            $password = "";
            $database = "web";
            
            $eMysqli = new eMysqli($host, $username, $password, $database);
            if ($eMysqli->connect_errno) {
                die('Failed to connect to MySQL');
            }
            
            return $eMysqli;
            
        }
        
        ///////////////////////////////////////////////////////////////////////////////////////
        //
        //  eMysqli Class
        //
        ///////////////////////////////////////////////////////////////////////////////////////
        
        class eMysqli extends mysqli {
            
            // RAM memory cleanup
            function freeMemory($queryRes){
                
                $queryRes->free();
                while($this->more_results()){
                    $this->next_result();
                    if($res = $this->store_result()){
                        $res->free(); 
                    }
                }
                
            }
            
            ///////////////////////////////////////////////////////////////////////////////////////
            // Calls a view and returns the result as array
            function callView($name){
                
                // Return variable
                $returnData = [];
                
                // ------- CALL VIEW -------
                
                $queryString = "SELECT * FROM `$name`";
                $queryRes = $this->query($queryString) or die($this->error);
                
                if($queryRes->field_count > 0){
                    
                    while($fetchResult = $queryRes->fetch_assoc()){
                        $returnData[] = $fetchResult;
                    }
                    
                    $this->freeMemory($queryRes); // Free memory
                    
                }
                
                return $returnData;
                
            }
            
            ///////////////////////////////////////////////////////////////////////////////////////
            // Calls a function and returns the result
            function callFunction($name, $input = []){
                
                // Return variable
                $returnData = [];
                
                // Escape all params... NO SQLI IS ALLOWED HERE
                for($i = 0; $i < count($input); $i++){
                    $input[$i] = '\'' . $this->real_escape_string($input[$i]) . '\'';
                }
                
                // ------- CALL FUNCTION -------
                
                $queryString = "SELECT `$name` (" . implode(",", $input) . ') as `output`';
                $queryRes = $this->query($queryString) or die($this->error);
                
                if($queryRes->field_count > 0){
                    
                    $fetchResult = $queryRes->fetch_assoc();
                    $returnData = $fetchResult['output'];
                    
                    $this->freeMemory($queryRes); // Free memory
                    
                }
                
                return $returnData;
                
            }
            
            ///////////////////////////////////////////////////////////////////////////////////////
            // Calls a procedure and returns the (result of the procedure + output) as array
            function callProcedure($name, $input = [], $output = []){
                
                // Return variable
                $returnData = [ 'pr' => [], 'out' => [] ];
                
                // Escape all params... NO SQLI IS ALLOWED HERE
                for($i = 0; $i < count($input); $i++){
                    $input[$i] = '\'' . $this->real_escape_string($input[$i]) . '\'';
                }
                
                // Merge all call params
                $params = array_merge($input, $output);
                
                // ------- CALL PROCEDURE -------
                
                $queryString = "CALL `$name` (" . implode(",", $params) . ')';
                $queryRes = $this->query($queryString) or die($this->error);
                
                if($queryRes->field_count > 0){
                    
                    while($fetchResult = $queryRes->fetch_assoc()){
                        $returnData['pr'][] = $fetchResult;
                    }
                    
                    $this->freeMemory($queryRes); // Free memory
                    
                }
                
                // ------- GET OUTPUT -------
                
                // If there are some output parameters
                if(count($output) > 0){
                    
                    $queryString = 'SELECT ' . implode(",", $output);
                    $queryRes = $this->query($queryString) or die($this->error);
                    
                    if($queryRes->field_count > 0){
                        
                        $fetchResult = $queryRes->fetch_assoc();
                        $returnData['out'] = $fetchResult;
                        
                        $this->freeMemory($queryRes); // Free memory
                        
                    }
                    
                }
                
                return $returnData;
                
            }
            
            ///////////////////////////////////////////////////////////////////////////////////////
            // Gets an output array and returns an html table
            function getHTML($input, $attributes = [], $caption = ''){
                
                // Output variable
                $htmlString = '';
                
                // If isnt an array, return value
                if(!is_array($input))
                    return $input;
                    
                // If array is empty, return ''
                if(count($input) == 0)
                    return '';
                    
                // If is a procedure result, concatenate both tables
                if(isset($input['pr']) && isset($input['out'])){
                    return $this->getHTML($input['pr'], $attributes, 'Procedure') . $this->getHTML($input['out'], $attributes, 'Output');
                }
                
                $multiLevel = is_array(array_values($input)[0]); // Is a multilevel array?
                
                $htmlString .= '<table';
                foreach($attributes as $key => $value){
                    $htmlString .= ' ' . $key . '="' . $value . '"';
                }
                $htmlString .= '>';
                if(!empty($caption))
                    $htmlString .= '<caption>' . $caption . '</caption>';
                
                // Print table header
                $htmlString .= '<tr>';
                $htmlString .= '<th>' . implode('</th><th>', array_keys($multiLevel?$input[0]:$input)) . '</th>';
                $htmlString .= '</tr>';
                
                if($multiLevel){
                    
                    for($i = 0; $i < count($input); $i++){
                        
                        $htmlString .= '<tr>';
                        $htmlString .= '<td>' . implode('</td><td>', array_values($input[$i])) . '</td>';
                        $htmlString .= '</tr>';
                        
                    }
                    
                } else {
                    
                    $htmlString .= '<tr>';
                    $htmlString .= '<td>' . implode('</td><td>', array_values($input)) . '</td>';
                    $htmlString .= '</tr>';
                    
                }
                
                $htmlString .= '</table>';
                
                return $htmlString;
                
            }

        }
        
    }

?>
