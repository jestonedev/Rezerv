<?php

    include_once "ldap.php";
    include_once "const.php";
    include_once "filter.php";
    include_once "request.php";
    include_once "ReportClass.php";
    include_once "CarsInfoClass.php";
    include_once "RepairActsClass.php";
    include_once "WaybillsClass.php";

    class DataTableData
    {
        private $aColumns = array();
        private $sTable;
        private $sIndexColumn;
        private $link;
        private $IQueryClass;

        public function __construct()
        {
            if ( ! $this->link = mysqli_connect( MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE ) )
            {
                $this->fatal_error( 'Could not open connection to server' );
            }
            mysqli_query($this->link, "SET NAMES 'utf8'");
            if (isset($_COOKIE["menu_code"]) && ($_COOKIE["menu_code"] == 1))
                $this->IQueryClass = new ReportClass();
            else
            if (isset($_COOKIE["menu_code"]) && ($_COOKIE["menu_code"] == 2))
                $this->IQueryClass = new CarsInfoClass();
            else
            if (isset($_COOKIE["menu_code"]) && ($_COOKIE["menu_code"] == 3))
                $this->IQueryClass = new RepairActsClass();
            else
            if (isset($_COOKIE["menu_code"]) && ($_COOKIE["menu_code"] == 4))
                $this->IQueryClass = new WaybillsClass();
            else
                $this->IQueryClass = new Request();
            $this->aColumns = $this->IQueryClass->Columns();
            $this->sTable = $this->IQueryClass->Table();
            $this->sIndexColumn = $this->IQueryClass->IndexColumn();
        }

        public function __destruct()
        {
            if ($this->link)
                mysqli_close($this->link);
        }

        private function fatal_error ( $sErrorMessage = '' )
        {
            header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
            die( $sErrorMessage );
        }

        private function Padding()
        {
            $sLimit = "";
            if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
            {
                $sLimit = "LIMIT ".mysql_real_escape_string( $_POST['iDisplayStart'] ).", ".
                    mysql_real_escape_string( $_POST['iDisplayLength'] );
            }
            return $sLimit;
        }

        private function Ordering()
        {
            $sOrder = "";
            if ( isset( $_POST['iSortCol_0'] ) )
            {
                $sOrder = "ORDER BY  ";
                for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
                {
                    if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
                    {
                        $sOrder .= $this->aColumns[ intval( $_POST['iSortCol_'.$i] ) ]."
				 	".mysql_real_escape_string( $_POST['sSortDir_'.$i] ) .", ";
                    }
                }

                $sOrder = substr_replace( $sOrder, "", -2 );
                if ( $sOrder == "ORDER BY" )
                {
                    $sOrder = "";
                }
            }
            return $sOrder;
        }

        private function Filtering()
        {
            $sWhere = $this->IQueryClass->Where();
            if ( isset($_POST['sSearch'])&& $_POST['sSearch'] != "" )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = "WHERE ";
                }
                else
                {
                    $sWhere .= " AND ";
                }
                $sWhere .= " (";
                for ( $i=0 ; $i<count($this->aColumns) ; $i++ )
                {
                    if ( isset($_POST['bSearchable_'.$i]) && $_POST['bSearchable_'.$i] == "true" )
                    {
                        $sWhere .= "(".$this->aColumns[$i]." LIKE '%".mysql_real_escape_string( $_POST['sSearch'] )."%') OR ";
                    }
                }
                $sWhere = substr_replace( $sWhere, "", -3 );
                $sWhere .= ')';
            }

            for ( $i=0 ; $i<count($this->aColumns) ; $i++ )
            {
                if (isset($_POST['bSearchable_'.$i])&& $_POST['bSearchable_'.$i] == "true" && $_POST['sSearch_'.$i] != '' )
                {
                    if ( $sWhere == "" )
                    {
                        $sWhere = "WHERE ";
                    }
                    else
                    {
                        $sWhere .= " AND ";
                    }
                    $sWhere .= $this->aColumns[$i]." LIKE '%".mysql_real_escape_string($_POST['sSearch_'.$i])."%' ";
                }
            }
            return $sWhere;
        }

        private function GetResult()
        {
            $query = "
		    SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $this->aColumns))."
		    FROM ";
            $query.= $this->sTable;
            $query.= " ".$this->Filtering();
            $query.= " ".$this->Ordering();
            $query.= " ".$this->Padding();
            $rResult = mysqli_query( $this->link, $query ) or $this->fatal_error( 'MySQL Error: ' . mysqli_stmt_errno() );
            return $rResult;
        }

        private function GetTotalRecords()
        {
            $sQuery = "SELECT COUNT(".$this->IQueryClass->IndexColumn().") AS RowCount FROM ".$this->IQueryClass->Table();
            $rResultTotal = mysqli_query($this->link, $sQuery) or $this->fatal_error( 'MySQL Error: ' . mysqli_stmt_errno() );
            $aResultTotal = mysqli_fetch_array($rResultTotal, MYSQLI_ASSOC);
            return $aResultTotal["RowCount"];
        }

        private function GetFilteredTotalRecords()
        {
            $sQuery = "SELECT FOUND_ROWS() AS RowCount";
            $rResultFilterTotal = mysqli_query( $this->link, $sQuery ) or fatal_error( 'MySQL Error: ' . mysqli_stmt_errno() );
            $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal, MYSQLI_ASSOC);
            return $aResultFilterTotal["RowCount"];
        }

        private function ConvertToJSON($query_result)
        {
            $ldap = new LDAP();
            $users_buffer = $ldap->GetAllUsers();
            $iFilteredTotal = $this->GetFilteredTotalRecords();
            $iTotal = $this->GetTotalRecords();
            $sOutput = '{';
            $sOutput .= '"sEcho": '.(isset($_POST['sEcho'])?intval($_POST['sEcho']):0).', ';
            $sOutput .= '"iTotalRecords": '.$iTotal.', ';
            $sOutput .= '"iTotalDisplayRecords": '.$iFilteredTotal.', ';
            $sOutput .= '"aaData": [ ';
            while ( $aRow = mysqli_fetch_array( $query_result, MYSQLI_ASSOC ) )
            {
                $sOutput .= "[";
                for ( $i=0 ; $i<count($this->aColumns) ; $i++ )
                {
                    $sOutput .= '"'.$this->IQueryClass->FilterColumnsData($this->aColumns[$i], $aRow[ $this->aColumns[$i] ]).'",';
                }
                $sOutput = substr_replace( $sOutput, "", -1 );
                $sOutput .= "],";
            }
            $sOutput = substr_replace( $sOutput, "", -1 );
            $sOutput .= '] }';
            return $sOutput;
        }

        public function GetDataTableData()
        {
            $sResult = $this->GetResult();
            return $this->ConvertToJSON($sResult);
        }

        public function GetDataTableColumns()
        {
            return $this->IQueryClass->DisplayColumnNames();
        }
    }


if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $DTD = new DataTableData();
    if (isset($_POST['action']) && $_POST['action'] == 'init_columns')
        echo $DTD->GetDataTableColumns();
    else
    	echo $DTD->GetDataTableData();
}