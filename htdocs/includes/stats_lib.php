<?php
#
# (c) C4G, Santosh Vempala, Ruban Monu and Amol Shintre
# Contains functions for calculating various statistics for reports
# [requires db_lib.php to be included, before including this file]
#

function week_to_date($week_num, $year)
{
	# Returns timestamp for the first day of the week
	# TODO: Move this to /includes/date_lib.php
	$week = $week_num;
	$Jan1 = mktime (1, 1, 1, 1, 1, $year);
	$iYearFirstWeekNum = (int) strftime("%W",mktime (1, 1, 1, 1, 1, $year));
	if ($iYearFirstWeekNum == 1)
	{
		$week = $week - 1;
	}
	$weekdayJan1 = date ('w', $Jan1);
	$FirstMonday = strtotime(((4-$weekdayJan1)%7-3) . ' days', $Jan1);
	$CurrentMondayTS = strtotime(($week) . ' weeks', $FirstMonday);
	return ($CurrentMondayTS);
}

function get_tests_done_count($lab_config, $test_type_id, $date_from, $date_to)
{
	$query_string = "";
	if($date_from == "" || $date_to == "") {
		$query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE result <> '' ".
			"AND test_type_id=$test_type_id";
	}
	else
	{
		$query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE result <> '' ".
			"AND test_type_id=$test_type_id ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to'".
				" )";
	}
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;
}

#nc40
function get_test_count_grouped($test_type_id, $date_from, $date_to, $gender, $age_from, $age_to, $completed = 0, $section = 0)
{
    $age_unit = "years";

    $query_string = "";

    $l_age_s = "-".$age_to." ".$age_unit;
    $l_age_t = strtotime($l_age_s);

    $u_age_s = "-".$age_from." ".$age_unit;
    $u_age_t = strtotime($u_age_s);

    $l_age_d = date('Y-m-d', $l_age_t);
    $u_age_d = date('Y-m-d', $u_age_t);
    $query_filter_dob = "dob >= '$l_age_d' AND dob < '$u_age_d'";
    $query_filter_partial_dob_yyyy_mm_dd = "partial_dob >= '$l_age_d' AND partial_dob < '$u_age_d'";

    #the following code block calculates age using the dob
    $l_age_temp = explode("-", $l_age_d);
    $u_age = (date("md", date("U", $l_age_t)) > date("md")
    ? ((date("Y") - $l_age_temp[0]) - 1)
    : (date("Y") - $l_age_temp[0]));
    $u_age_temp = explode("-", $u_age_d);
    $l_age = (date("md", date("U", $u_age_t)) > date("md")
    ? ((date("Y") - $u_age_temp[0]) - 1)
    : (date("Y") - $u_age_temp[0]));

    $l_age_m = date('Y-m', $l_age_t);
    $u_age_m = date('Y-m', $u_age_t);
    $query_filter_partial_dob_yyyy_mm = "partial_dob >= '$l_age_m' AND partial_dob < '$u_age_m'";

    $l_age_y = date('Y', $l_age_t);
    $u_age_y = date('Y', $u_age_t);
    $query_filter_partial_dob_yyyy = "partial_dob >= '$l_age_y' AND partial_dob < '$u_age_y'";


    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
            "SELECT COUNT(*) as count_val FROM test ".
            "WHERE test_type_id=$test_type_id ".
            "AND specimen_id IN ( ".
                "SELECT specimen_id FROM specimen ".
                "WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                                    ")".
				" )";
    }
    else
    {
        $query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
                        "AND result <> '' ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                                    ")".
				" )";
    }
	$record = query_associative_one($query_string);
        $count_val = $record['count_val'];
    $u_yr = date('Y') - $age_from;
    $l_yr = date('Y') - $age_to;
    if($completed == 0)
    {
            $query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND partial_dob >= '$l_yr' AND partial_dob < '$u_yr'".
                                    ")".
				" )";
    }
    else
    {
        $query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
                        "AND result <> '' ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND partial_dob >= '$l_yr' AND partial_dob < '$u_yr'".
                                    ")".
				" )";
    }
    $record = query_associative_one($query_string);
    $count_val = $count_val + $record['count_val'];
    return $count_val;
}


function get_sitewise_count_grouped_country_dir($test_type_id, $site_id, $date_from, $date_to, $gender, $age_from, $age_to, $age_unit, $completed = 0, $section = 0)
{

    if($age_unit == 1)
        $age_unit = "years";
    else if($age_unit == 2)
        $age_unit = "months";
    else if($age_unit == 3)
        $age_unit = "weeks";
    else if($age_unit == 4)
        $age_unit = "days";

    $query_string = "";



    $array=array_map('intval', explode(',', $site_id));
    $sitearray = implode("','",$array);

    $l_age_s = "-".$age_to." ".$age_unit;
    $l_age_t = strtotime($l_age_s);

    $u_age_s = "-".$age_from." ".$age_unit;
    $u_age_t = strtotime($u_age_s);

    $l_age_d = date('Y-m-d', $l_age_t);
    $u_age_d = date('Y-m-d', $u_age_t);
    $query_filter_dob = "dob >= '$l_age_d' AND dob < '$u_age_d'";
    $query_filter_partial_dob_yyyy_mm_dd = "partial_dob >= '$l_age_d' AND partial_dob < '$u_age_d'";

    #the following code block calculates age using the dob
    $l_age_temp = explode("-", $l_age_d);
    $u_age = (date("md", date("U", $l_age_t)) > date("md")
        ? ((date("Y") - $l_age_temp[0]) - 1)
        : (date("Y") - $l_age_temp[0]));
    $u_age_temp = explode("-", $u_age_d);
    $l_age = (date("md", date("U", $u_age_t)) > date("md")
        ? ((date("Y") - $u_age_temp[0]) - 1)
        : (date("Y") - $u_age_temp[0]));

    $l_age_m = date('Y-m', $l_age_t);
    $u_age_m = date('Y-m', $u_age_t);
    $query_filter_partial_dob_yyyy_mm = "partial_dob >= '$l_age_m' AND partial_dob < '$u_age_m'";

    $l_age_y = date('Y', $l_age_t);
    $u_age_y = date('Y', $u_age_t);
    $query_filter_partial_dob_yyyy = "partial_dob >= '$l_age_y' AND partial_dob < '$u_age_y'";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
        $query_string =
            "SELECT COUNT(*) as count_val FROM test ".
            "WHERE test_type_id=$test_type_id ".
            "AND specimen_id IN ( ".
            "SELECT specimen_id FROM specimen ".
            "WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
            "AND site_id IN (".$sitearray.")".
            "AND patient_id IN ( ".
            "SELECT patient_id from patient ".
            "WHERE sex = '$gender' ".
            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
            " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
            " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
            " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
            " ELSE age >= $l_age and age < $u_age".
            " END ".
            ")".
            " )";
    }
    else
    {
        $query_string =
            "SELECT COUNT(*) as count_val FROM test ".
            "WHERE test_type_id=$test_type_id ".
            "AND result <> '' ".
            "AND specimen_id IN ( ".
            "SELECT specimen_id FROM specimen ".
            "WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
            "AND site_id IN (".$sitearray.")".
            "AND patient_id IN ( ".
            "SELECT patient_id from patient ".
            "WHERE sex = '$gender' ".
            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
            " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
            " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
            " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
            " ELSE age >= $l_age and age < $u_age".
            " END ".
            ")".
            " )";
    }

    $record = query_associative_one($query_string);
    $count_val = $record['count_val'];
    #return $query_string;
    return $count_val;
}


function get_test_count_grouped_country_dir($test_type_id, $date_from, $date_to, $gender, $age_from, $age_to, $age_unit, $completed = 0, $section = 0)
{

    if($age_unit == 1)
        $age_unit = "years";
    else if($age_unit == 2)
        $age_unit = "months";
    else if($age_unit == 3)
        $age_unit = "weeks";
    else if($age_unit == 4)
        $age_unit = "days";

    $query_string = "";

    $l_age_s = "-".$age_to." ".$age_unit;
    $l_age_t = strtotime($l_age_s);

    $u_age_s = "-".$age_from." ".$age_unit;
    $u_age_t = strtotime($u_age_s);

    $l_age_d = date('Y-m-d', $l_age_t);
    $u_age_d = date('Y-m-d', $u_age_t);
    $query_filter_dob = "dob >= '$l_age_d' AND dob < '$u_age_d'";
    $query_filter_partial_dob_yyyy_mm_dd = "partial_dob >= '$l_age_d' AND partial_dob < '$u_age_d'";

    #the following code block calculates age using the dob
    $l_age_temp = explode("-", $l_age_d);
    $u_age = (date("md", date("U", $l_age_t)) > date("md")
    ? ((date("Y") - $l_age_temp[0]) - 1)
    : (date("Y") - $l_age_temp[0]));
    $u_age_temp = explode("-", $u_age_d);
    $l_age = (date("md", date("U", $u_age_t)) > date("md")
    ? ((date("Y") - $u_age_temp[0]) - 1)
    : (date("Y") - $u_age_temp[0]));

    $l_age_m = date('Y-m', $l_age_t);
    $u_age_m = date('Y-m', $u_age_t);
    $query_filter_partial_dob_yyyy_mm = "partial_dob >= '$l_age_m' AND partial_dob < '$u_age_m'";

    $l_age_y = date('Y', $l_age_t);
    $u_age_y = date('Y', $u_age_t);
    $query_filter_partial_dob_yyyy = "partial_dob >= '$l_age_y' AND partial_dob < '$u_age_y'";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
            "SELECT COUNT(*) as count_val FROM test ".
            "WHERE test_type_id=$test_type_id ".
            "AND specimen_id IN ( ".
                "SELECT specimen_id FROM specimen ".
                "WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                                    ")".
                " )";
    }
    else
    {
        $query_string =
            "SELECT COUNT(*) as count_val FROM test ".
            "WHERE test_type_id=$test_type_id ".
                        "AND result <> '' ".
            "AND specimen_id IN ( ".
                "SELECT specimen_id FROM specimen ".
                "WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                                    ")".
                " )";
    }

    $record = query_associative_one($query_string);
    $count_val = $record['count_val'];
    #return $query_string;
    return $count_val;
}

# nc40
function get_test_count_grouped2($test_type_id, $date_from, $date_to, $gender, $completed = 0, $section = 0)
{
    $query_string = "";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    ")".
				" )";
    }
    else
    {
        $query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
                        "AND result <> '' ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to' ".
                                "AND patient_id IN ( ".
                                    "SELECT patient_id from patient ".
                                    "WHERE sex = '$gender' ".
                                    ")".
				" )";
    }
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;
}
function get_specimen_count_grouped_country_dir($specimen_type_id, $date_from, $date_to, $gender, $age_from, $age_to, $age_unit, $completed = 0, $section = 0)
{
    if($age_unit == 1)
        $age_unit = "years";
    else if($age_unit == 2)
        $age_unit = "months";
    else if($age_unit == 3)
        $age_unit = "weeks";
    else if($age_unit == 4)
        $age_unit = "days";

    $query_string = "";

    $l_age_s = "-".$age_to." ".$age_unit;
    $l_age_t = strtotime($l_age_s);

    $u_age_s = "-".$age_from." ".$age_unit;
    $u_age_t = strtotime($u_age_s);

    $l_age_d = date('Y-m-d', $l_age_t);
    $u_age_d = date('Y-m-d', $u_age_t);
    $query_filter_dob = "dob >= '$l_age_d' AND dob < '$u_age_d'";
    $query_filter_partial_dob_yyyy_mm_dd = "partial_dob >= '$l_age_d' AND partial_dob < '$u_age_d'";

    #the following code block calculates age using the dob
    $l_age_temp = explode("-", $l_age_d);
    $u_age = (date("md", date("U", $l_age_t)) > date("md")
    ? ((date("Y") - $l_age_temp[0]) - 1)
    : (date("Y") - $l_age_temp[0]));
    $u_age_temp = explode("-", $u_age_d);
    $l_age = (date("md", date("U", $u_age_t)) > date("md")
    ? ((date("Y") - $u_age_temp[0]) - 1)
    : (date("Y") - $u_age_temp[0]));

    $l_age_m = date('Y-m', $l_age_t);
    $u_age_m = date('Y-m', $u_age_t);
    $query_filter_partial_dob_yyyy_mm = "partial_dob >= '$l_age_m' AND partial_dob < '$u_age_m'";

    $l_age_y = date('Y', $l_age_t);
    $u_age_y = date('Y', $u_age_t);
    $query_filter_partial_dob_yyyy = "partial_dob >= '$l_age_y' AND partial_dob < '$u_age_y'";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                  " ELSE age >= $l_age and age < $u_age".
                            " END ".
                            ")";
    }
    else
    {
        $query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_reported <> NULL".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                  " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                  " ELSE age >= $l_age and age < $u_age".
                            " END ".
                            ")";
    }
    $record = query_associative_one($query_string);
    $count_val = $record['count_val'];
    #return $query_string;
    return $count_val;
}

#nc40
function get_specimen_count_grouped($specimen_type_id, $date_from, $date_to, $gender, $age_from, $age_to, $completed = 0, $section = 0)
{

    $age_unit = "years";

    $query_string = "";

    $l_age_s = "-".$age_to." ".$age_unit;
    $l_age_t = strtotime($l_age_s);

    $u_age_s = "-".$age_from." ".$age_unit;
    $u_age_t = strtotime($u_age_s);

    $l_age_d = date('Y-m-d', $l_age_t);
    $u_age_d = date('Y-m-d', $u_age_t);
    $query_filter_dob = "dob >= '$l_age_d' AND dob < '$u_age_d'";
    $query_filter_partial_dob_yyyy_mm_dd = "partial_dob >= '$l_age_d' AND partial_dob < '$u_age_d'";

    #the following code block calculates age using the dob
    $l_age_temp = explode("-", $l_age_d);
    $u_age = (date("md", date("U", $l_age_t)) > date("md")
    ? ((date("Y") - $l_age_temp[0]) - 1)
    : (date("Y") - $l_age_temp[0]));
    $u_age_temp = explode("-", $u_age_d);
    $l_age = (date("md", date("U", $u_age_t)) > date("md")
    ? ((date("Y") - $u_age_temp[0]) - 1)
    : (date("Y") - $u_age_temp[0]));

    $l_age_m = date('Y-m', $l_age_t);
    $u_age_m = date('Y-m', $u_age_t);
    $query_filter_partial_dob_yyyy_mm = "partial_dob >= '$l_age_m' AND partial_dob < '$u_age_m'";

    $l_age_y = date('Y', $l_age_t);
    $u_age_y = date('Y', $u_age_t);
    $query_filter_partial_dob_yyyy = "partial_dob >= '$l_age_y' AND partial_dob < '$u_age_y'";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
            "SELECT COUNT(*) as count_val FROM specimen ".
            "WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                            ")";
    }
    else
    {
        $query_string =
            "SELECT COUNT(*) as count_val FROM specimen ".
            "WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_reported <> NULL".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            "AND CASE WHEN dob is not null OR dob <> '' THEN ".$query_filter_dob.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 10) THEN ".$query_filter_partial_dob_yyyy_mm_dd.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 7) THEN ".$query_filter_partial_dob_yyyy_mm.
                                    " WHEN (CHAR_LENGTH(partial_dob) = 4) THEN ".$query_filter_partial_dob_yyyy.
                                    " ELSE age >= $l_age and age < $u_age".
                                    " END ".
                            ")";
    }
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;
}

# nc40
function get_specimen_count_grouped2($specimen_type_id, $date_from, $date_to, $gender, $completed = 0, $section = 0)
{
    $query_string = "";

    //$query_string = "SELECT count(*) as count_val FROM test WHERE test_type_id = $test_type_id";
    if($completed == 0)
    {
            $query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            ")";
    }
    else
    {
        $query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE specimen_type_id=$specimen_type_id ".
                        "AND date_reported <> NULL".
                        "AND date_collected BETWEEN '$date_from' AND '$date_to' ".
                        "AND patient_id IN ( ".
                            "SELECT patient_id from patient ".
                            "WHERE sex = '$gender' ".
                            ")";
    }
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;
}


function get_test_count($lab_config, $test_type_id, $date_from, $date_to)
{

$query_string = "";
	if($date_from == "" || $date_to == "")
	{
		$query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id";
	}
	else
	{
		$query_string =
			"SELECT COUNT(*) as count_val FROM test ".
			"WHERE test_type_id=$test_type_id ".
			"AND specimen_id IN ( ".
				"SELECT specimen_id FROM specimen ".
				"WHERE date_collected BETWEEN '$date_from' AND '$date_to'".
				" )";
	}
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;

}
function get_specimen_count($lab_config, $specimen_type_id, $date_from, $date_to)
{
	$query_string = "";
	if($date_from == "" || $date_to == "")
	{
		$query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE specimen_type_id=$specimen_type_id";
	}
	else
	{
		$query_string =
			"SELECT COUNT(*) as count_val FROM specimen ".
			"WHERE date_collected BETWEEN '$date_from' AND '$date_to'".
			"AND specimen_type_id=$specimen_type_id";
	}
	$record = query_associative_one($query_string);
	$count_val = $record['count_val'];
	return $count_val;
}

function get_discrete_value_test_types($lab_config)
{
	# Returns test type IDs for tests that have explicit P/N results
	$saved_db = DbUtil::switchToLabConfigRevamp($lab_config->id);
	$retval = array();
	$query_string =
		"SELECT DISTINCT test_type_id FROM test_type ".
		"WHERE test_type_id IN ( ".
			"SELECT test_type_id FROM lab_config_test_type ".
			"WHERE lab_config_id=$lab_config->id ".
		" ) AND disabled=0";
	$resultset = query_associative_all($query_string);
	foreach($resultset as $record)
	{
		$test_type_id = $record['test_type_id'];
		$query_string2 =
			"SELECT DISTINCT ttm.test_type_id FROM test_type_measure ttm ".
			"WHERE ttm.measure_id IN ( ".
				"SELECT measure_id FROM measure ".
				"WHERE ( ".
					"range LIKE '%/N%' OR range LIKE '%N/%' OR ".
					"range LIKE '%/negative%' OR range LIKE '%negative/%' OR ".
					"range LIKE '%/negatif%' OR range LIKE '%negatif/%' OR ".
					"range LIKE '%/n�gatif%' OR range LIKE '%n�gatif/%' ".
				" ) ".
			" ) ".
			"AND ttm.test_type_id=$test_type_id";
		$record2 = query_associative_one($query_string2);
		if($record2 == null)
			continue;
		$retval[] = $record2['test_type_id'];
	}
	DbUtil::switchRestore($saved_db);
	return $retval;
}

function get_range_value_test_types($lab_config)
{
	if($lab_config == null)
		return null;
	$discrete_tests = get_discrete_value_test_types($lab_config);
	$all_tests = get_lab_config_test_types($lab_config->id);
	$range_tests = array_values(array_intersect($all_tests, $discrete_tests));
	return $range_tests;
}


class MeasureMeta
{
	public $name;
	public $rangetype;
	public $rangeValues;
	public $rangeParts;
	public $countParts;
	public $countTotal;

	public static $DISCRETE = 1;
	public static $CONTINUOUS = 2;
}


class DiseaseSet
{
	# Class for keeping track of Disease Report params
	public $patientAge;
	public $patientGender;
	public $resultValues;
}


class DiseaseSetFilter
{
	# Class for creating filters on Disease Report params
	# Used in StatsLib::getDiseaseFilterCount()
	public $patientAgeRange;
	public $patientGender;
	public $measureId;
	public $rangeType;
	public $rangeValues;

	public static $DISCRETE = 1;
	public static $CONTINUOUS = 2;
}


class StatsLib
{
	public static $diseaseSetList = array(); # Used while aggregating Disease Report stats

	public static function  get_ip()
{
	$ip_address=$_SERVER['HTTP_X_FORWARDED_FOR'];

	if ($ip_address==NULL){
	$ip_address=$_SERVER['REMOTE_ADDR']; }
	$ip_address="http://".$ip_address.":4001/login.php";
	return($ip_address);
}
	public static function getPercentile($list, $ile_value)
	{
		# Returns the percentile value from the given list
		$num_values = count($list);
		sort($list);
		$mark = ceil(round($ile_value/100, 2) * $num_values);
		return $list[$mark-1];
	}

	public static function getTatStats($lab_config, $date_from, $date_to)
	{
		# Returns a list of {test_type, Turnaround Time (tat), total specimens} for a given time period
		# Fetch all test types handled at this lab
		$retval = array();
		$test_type_list = get_lab_config_test_types($lab_config->id);
		# For each test type, fetch diff between date collected and date of result entry
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		foreach($test_type_list as $test_type_id)
		{
			$resultset = get_completed_tests_by_type($test_type_id, $date_from, $date_to);
			# test_list now contains {ts, specimen_id, date_collected} entries
			# ts and date_collected are in Unix timestamp format
			# Calculate average tat value
			if(count($resultset) == 0)
			{
				# No tests completed during the date interval
				$curr_entry = array();
				$curr_entry[] = 0;
				$curr_entry[] = 0;

				# Append to result
				$retval[$test_type_id] = $curr_entry;
				continue;
			}

			# Calculate TAT value
			$cumulative_diff = 0;

			foreach($resultset as $record)
			{
				$date_collected = $record['date_collected'];
				$date_ts = $record['ts'];
				$date_diff = $date_ts - $date_collected;

				$cumulative_diff += $date_diff;
			}
			$avg_tat = round(($cumulative_diff/(60*60*24))/count($resultset), 2);
			# Record {tat value, total specimens} handled
			$curr_entry = array();
			$curr_entry[] = $avg_tat;
			$curr_entry[] = count($resultset);
			# Append to result
			$retval[$test_type_id] = $curr_entry;
		}
		DbUtil::switchRestore($saved_db);
		return $retval;
	}

	public static function getTatMonthlyProgressionStats($lab_config, $test_type_id, $date_from, $date_to, $include_pending=false)
	{
		# Calculates monthly progression of TAT values for a given test type and time period
		global $DEFAULT_PENDING_TAT; # Default TAT value for pending tests (in days)
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$resultset = get_completed_tests_by_type($test_type_id, $date_from, $date_to);
		# {resultentry_ts, specimen_id, date_collected_ts}
		$progression_val = array();
		$progression_count = array();
		$percentile_tofind = 90;
		$percentile_count = array();
		$goal_val = array();
		# Build list as {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
		# For completed tests
		foreach($resultset as $record)
		{
			$date_collected = $record['date_collected'];
			$date_collected_parsed = date("Y-m-d", $date_collected);
			$date_collected_parts = explode("-", $date_collected_parsed);
			$month_ts = mktime(0, 0, 0, $date_collected_parts[1], 0, $date_collected_parts[0]);
			$month_ts_datetime = date("Y-m-d H:i:s", $month_ts);
			$date_ts = $record['ts'];
			$date_diff = ($date_ts - $date_collected);
			if(!isset($progression_val[$month_ts]))
			{
				$goal_tat[$month_ts] = $lab_config->getGoalTatValue($test_type_id, $month_ts_datetime);
				$progression_val[$month_ts] = array();
				$progression_val[$month_ts][0] = $date_diff;
				$percentile_count[$month_ts] = array();
				$percentile_count[$month_ts][] = $date_diff;
				$progression_count[$month_ts] = 1;
				$progression_val[$month_ts][3] = array();
				$progression_val[$month_ts][4] = array();
			}
			else
			{
				$progression_val[$month_ts][0] += $date_diff;
				$percentile_count[$month_ts][] = $date_diff;
				$progression_count[$month_ts] += 1;
			}
			if($date_diff/(60*60*24) > $goal_tat[$month_ts])
			{
				# Add to list of TAT exceeded specimens
				$progression_val[$month_ts][3][] = $record['specimen_id'];
			}
		}
		if($include_pending === true)
		{
			$pending_tat_value = $lab_config->getPendingTatValue(); # in hours
			# Update the above list {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
			# For pending tests in this time duration
			$resultset_pending = get_pendingtat_tests_by_type($test_type_id, $date_from, $date_to);
			$num_pending = count($resultset_pending);
			foreach($resultset_pending as $record)
			{
				$date_collected = $record['date_collected'];
				$date_collected_parsed = date("Y-m-d", $date_collected);
				$date_collected_parts = explode("-", $date_collected_parsed);
				$month_ts = mktime(0, 0, 0, $date_collected_parts[1], 0, $date_collected_parts[0]);
				$month_ts_datetime = date("Y-m-d H:i:s", $month_ts);
				$date_diff = $pending_tat_value*60*60;
				if(!isset($progression_val[$month_ts]))
				{
					$goal_tat[$month_ts] = $lab_config->getGoalTatValue($test_type_id, $month_ts_datetime);
					$progression_val[$month_ts] = array();
					$progression_val[$month_ts][0] = $date_diff;
					$percentile_count[$month_ts] = array();
					$percentile_count[$month_ts][] = $date_diff;
					$progression_count[$month_ts] = 1;
					$progression_val[$month_ts][3] = array();
					$progression_val[$month_ts][4] = array();
				}
				else
				{
					$progression_val[$month_ts][0] += $date_diff;
					$percentile_count[$month_ts][] = $date_diff;
					$progression_count[$month_ts] += 1;
				}
				# Add to list of TAT pending specimens
				$progression_val[$month_ts][4][] = $record['specimen_id'];
			}
		}
		foreach($progression_val as $key=>$value)
		{
			# Find average value
			$progression_val[$key][0] = $value[0]/$progression_count[$key];
			# Convert from sec timestamp to days
			$progression_val[$key][0] = ($progression_val[$key][0]/(60*60*24));
			# Determine percentile value
			$progression_val[$key][1] = StatsLib::getPercentile($percentile_count[$key], $percentile_tofind);
			# Convert from sec timestamp to days
			$progression_val[$key][1] = $progression_val[$key][1]/(60*60*24);
			$progression_val[$key][2] = $goal_tat[$key];
		}
		DbUtil::switchRestore($saved_db);
		return $progression_val;
	}

	public static function getTatWeeklyProgressionStats($lab_config, $test_type_id, $date_from, $date_to, $include_pending=false)
	{
		# Calculates weekly progression of TAT values for a given test type and time period
		global $DEFAULT_PENDING_TAT; # Default TAT value for pending tests (in days)
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$resultset = get_completed_tests_by_type($test_type_id, $date_from, $date_to);
		# {resultentry_ts, specimen_id, date_collected_ts}
		$progression_val = array();
		$progression_count = array();
		$percentile_tofind = 90;
		$percentile_count = array();
		$goal_val = array();
		# Return {week=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
		foreach($resultset as $record) {
			$date_collected = $record['date_collected'];
			$week_collected = date("W", $date_collected);
			$year_collected = date("Y", $date_collected);
			$week_ts = week_to_date($week_collected, $year_collected);
			$week_ts_datetime = date("Y-m-d H:i:s", $week_ts);
			$date_ts = $record['ts'];
			$date_diff = ($date_ts - $date_collected);
			if(!isset($progression_val[$week_ts])) {
				$progression_val[$week_ts] = array();
				$progression_val[$week_ts][0] = $date_diff;
				$percentile_count[$week_ts] = array();
				$percentile_count[$week_ts][] = $date_diff;
				$progression_count[$week_ts] = 1;
				$goal_tat[$week_ts] = $lab_config->getGoalTatValue($test_type_id, $week_ts_datetime);
				$progression_val[$week_ts][3] = array();
				$progression_val[$week_ts][4] = array();
			}
			else {
				$progression_val[$week_ts][0] += $date_diff;
				$percentile_count[$week_ts][] = $date_diff;
				$progression_count[$week_ts] += 1;
			}
			if($date_diff/(60*60*24) > $goal_tat[$week_ts]) {
				$progression_val[$week_ts][3][] = $record['specimen_id'];
			}
		}
		if($include_pending === true) {
			$pending_tat_value = $lab_config->getPendingTatValue(); # in hours
			# Update the above list {week=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
			# For pending tests in this time duration
			$resultset_pending = get_pendingtat_tests_by_type($test_type_id, $date_from, $date_to);
			$num_pending = count($resultset_pending);
			foreach($resultset_pending as $record) {

				$date_collected = $record['date_collected'];
				$week_collected = date("W", $date_collected);
				$year_collected = date("Y", $date_collected);
				$week_ts = week_to_date($week_collected, $year_collected);
				$week_ts_datetime = date("Y-m-d H:i:s", $week_ts);
				$date_ts = $record['ts'];
				$date_diff = $pending_tat_value*60*60;;
				if(!isset($progression_val[$week_ts]))
				{
					$progression_val[$week_ts] = array();
					$progression_val[$week_ts][0] = $date_diff;
					$percentile_count[$week_ts] = array();
					$percentile_count[$week_ts][] = $date_diff;
					$progression_count[$week_ts] = 1;
					$goal_tat[$week_ts] = $lab_config->getGoalTatValue($test_type_id, $week_ts_datetime);
					$progression_val[$week_ts][3] = array();
					$progression_val[$week_ts][4] = array();
				}
				else
				{
					$progression_val[$week_ts][0] += $date_diff;
					$percentile_count[$week_ts][] = $date_diff;
					$progression_count[$week_ts] += 1;
				}
				# Add to list of TAT pending specimens
				$progression_val[$week_ts][4][] = $record['specimen_id'];
			}
		}
		foreach($progression_val as $key=>$value) {
			# Find average value
			$progression_val[$key][0] = $value[0]/$progression_count[$key];
			# Convert from sec timestamp to days
			$progression_val[$key][0] = ($progression_val[$key][0]/(60*60*24));
			# Determine percentile value
			$progression_val[$key][1] = StatsLib::getPercentile($percentile_count[$key], $percentile_tofind);
			# Convert from sec timestamp to days
			$progression_val[$key][1] = $progression_val[$key][1]/(60*60*24);
			$progression_val[$key][2] = $goal_tat[$key];
		}
		DbUtil::switchRestore($saved_db);
		# Return {week=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
		return $progression_val;
	}

	public static function getTatDailyProgressionStats($lab_config, $test_type_id, $date_from, $date_to, $include_pending=false)
	{
		# Calculates weekly progression of TAT values for a given test type and time period
		global $DEFAULT_PENDING_TAT; # Default TAT value for pending tests (in days)
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$resultset = get_completed_tests_by_type($test_type_id, $date_from, $date_to);
		# {resultentry_ts, specimen_id, date_collected_ts}
		$progression_val = array();
		$progression_count = array();
		$percentile_tofind = 90;
		$percentile_count = array();
		$goal_val = array();
		# Return {day=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
		foreach($resultset as $record)
		{
			$date_collected = $record['date_collected'];
			$date_ts = $record['ts'];
			$date_diff = ($date_ts - $date_collected);
			$day_ts = $date_collected;
			$day_ts_datetime = date("Y-m-d H:i:s", $day_ts);
			if(!isset($progression_val[$day_ts]))
			{
				$progression_val[$day_ts] = array();
				$progression_val[$day_ts][0] = $date_diff;
				$percentile_count[$day_ts] = array();
				$percentile_count[$day_ts][] = $date_diff;
				$progression_count[$day_ts] = 1;
				$goal_tat[$day_ts] = $lab_config->getGoalTatValue($test_type_id, $day_ts_datetime);
				$progression_val[$day_ts][3] = array();
				$progression_val[$day_ts][4] = array();
			}
			else
			{
				$progression_val[$day_ts][0] += $date_diff;
				$percentile_count[$day_ts][] = $date_diff;
				$progression_count[$day_ts] += 1;
			}
			if($date_diff/(60*60*24) > $goal_tat[$day_ts])
			{
				# Add to list of TAT exceeded specimens
				$progression_val[$day_ts][3][] = $record['specimen_id'];
			}
		}
		if($include_pending == true)
		{
			$pending_tat_value = $lab_config->getPendingTatValue(); # in hours
			# Update the above list {day=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
			# For pending tests in this time duration
			$resultset_pending = get_pendingtat_tests_by_type($test_type_id, $date_from, $date_to);
			$num_pending = count($resultset_pending);
			foreach($resultset_pending as $record)
			{
				$date_collected = $record['date_collected'];
				$date_ts = $record['ts'];
				$date_diff = $pending_tat_value*60*60;;
				$day_ts = $date_collected;
				$day_ts_datetime = date("Y-m-d H:i:s", $day_ts);
				if(!isset($progression_val[$day_ts]))
				{
					$progression_val[$day_ts] = array();
					$progression_val[$day_ts][0] = $date_diff;
					$percentile_count[$day_ts] = array();
					$percentile_count[$day_ts][] = $date_diff;
					$progression_count[$day_ts] = 1;
					$goal_tat[$day_ts] = $lab_config->getGoalTatValue($test_type_id, $day_ts_datetime);
					$progression_val[$day_ts][3] = array();
					$progression_val[$day_ts][4] = array();
				}
				else
				{
					$progression_val[$day_ts][0] += $date_diff;
					$percentile_count[$day_ts][] = $date_diff;
					$progression_count[$day_ts] += 1;
				}
				# Add to list of TAT pending specimens
				$progression_val[$day_ts][4][] = $record['specimen_id'];
			}
		}
		foreach($progression_val as $key=>$value)
		{
			# Find average value
			$progression_val[$key][0] = $value[0]/$progression_count[$key];
			# Convert from sec timestamp to days
			$progression_val[$key][0] = ($progression_val[$key][0]/(60*60*24));
			# Determine percentile value
			$progression_val[$key][1] = StatsLib::getPercentile($percentile_count[$key], $percentile_tofind);
			# Convert from sec timestamp to days
			$progression_val[$key][1] = $progression_val[$key][1]/(60*60*24);
			$progression_val[$key][2] = $goal_tat[$key];
		}
		DbUtil::switchRestore($saved_db);
		# Return {week=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}
		return $progression_val;
	}

	public static function getTestsDoneStats($lab_config, $date_from, $date_to)
	{
		# Returns a list of {test_type, number of tests performed} for the given time period
		$retval = array();
		# Fetch all test types in this lab configuration
		$test_type_list = get_lab_config_test_types($lab_config->id);
		# Count number of tests performed for each type
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$tests_done_list = array();
		$tests_list=array();

		foreach($test_type_list as $test_type_id)
		{
			$count_value = get_tests_done_count($lab_config, $test_type_id, $date_from, $date_to);
			$test_count=get_test_count($lab_config, $test_type_id, $date_from, $date_to);
			$tests_done_count_list[] = $count_value;
			$tests_pending_list[]=$test_count-$count_value;


		}

		for($i = 0; $i < count($test_type_list); $i++)
		{
			# Add to return value if test count not zero
			if($tests_pending_list[$i] != 0)
				$retval[$test_type_list[$i]] = array($tests_done_count_list[$i],$tests_pending_list[$i]);
		}
		DbUtil::switchRestore($saved_db);
		return $retval;
	}
	public static function getTestsDoneStatsAggregate($date_from, $date_to)
	{
		$site_list = get_site_list($_SESSION['user_id']);

		$userId = $_SESSION['user_id'];
		$saved_db = DbUtil::switchToGlobal();
		$query = "SELECT * FROM test_mapping WHERE user_id = $userId";
		$resultset = query_associative_all($query);
		foreach($resultset as $record) {
				$labIdTestIds = explode(';',$record['lab_id_test_id']);
				foreach($labIdTestIds as $labIdTestId) {
					$labIdTestId = explode(':',$labIdTestId);
					$labId = $labIdTestId[0];
					$testId= $labIdTestId[1];
					$test_type_list_all[$labId][] = $testId;
					$test_type_names[$labId][] = $record['test_name'];
				}
		}
		DbUtil::switchRestore($saved_db);
		$retval = array();

		$test_done_count = 0;
		$test_pending_count = 0;
		foreach( $site_list as $key => $value) {
			$lab_config = LabConfig::getById($key);
			# Returns a list of {test_type, number of tests performed} for the given time period
			$test_type_list = array();
			$test_type_list = $test_type_list_all[$key];
			$testNames = $test_type_names[$key];

			# Count number of tests performed for each type
			$saved_db = DbUtil::switchToLabConfig($lab_config->id);
			$tests_done_list = array();
			$tests_list=array();

			foreach($test_type_list as $test_type_id) {
				$count_value = get_tests_done_count($lab_config, $test_type_id, $date_from, $date_to);
				$test_count=get_test_count($lab_config, $test_type_id, $date_from, $date_to);
				$tests_done_count_list[] = $count_value;
				$tests_pending_list[]=$test_count-$count_value;
			}

			for($i = 0; $i < count($test_type_list); $i++) {
				# Add to return value if test count not zero
				$testName = $testNames[$i];
				if($tests_pending_list[$i] != 0) {
					$test_done_count += intval($tests_done_count_list[$i]);
					$test_pending_count += intval($tests_pending_list[$i]);
					$retval[$testName] = array($test_done_count,$test_pending_count);
				}

			}
			DbUtil::switchRestore($saved_db);
		}
		return $retval;
	}

	public static function getDoctorStats($lab_config,$date_from,$date_to) {
	$retval=array();
	$saved_db = DbUtil::switchToLabConfig($lab_config->id);
	$query_string="SELECT doctor,COUNT(specimen_type_id) AS specimen,COUNT(DISTINCT patient_id) as patient FROM specimen ".
					"WHERE (date_collected BETWEEN '$date_from' AND '$date_to' ) ".
					"GROUP BY doctor";
				//echo($query_string);
	$resultset = query_associative_all($query_string);

			if(count($resultset) == 0 || $resultset == null)
		{

			DbUtil::switchRestore($saved_db);
			return;
		}

	foreach($resultset AS $record)
	{
	$doctor_name=$record['doctor'];
	$query_string1="SELECT COUNT(test_type_id) as test FROM specimen s , test t ".
			"WHERE s.specimen_id=t.specimen_id ".
			"AND doctor='$doctor_name'".
			"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
			"GROUP BY DOCTOR";

		$record1 = query_associative_one($query_string1);
	if($doctor_name=="")
	$doctor_name="Not Known";
	$patient_count=$record['patient'];
	$specimen_count=$record['specimen'];
	$test_count=$record1['test'];
	$retval[$doctor_name] = array($patient_count,$specimen_count,$test_count);
	}

	DbUtil::switchRestore($saved_db);
	return($retval);


	}
	public static function getSpecimenCountStats($lab_config, $date_from, $date_to)
	{
		# Returns a list of {specimen_type, number of specimens handled} for the given time period
		$retval = array();
		# Fetch all specimen types in this lab configuration
		$specimen_type_list = get_lab_config_specimen_types($lab_config->id);
		# Count number of specimens handled for each type
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$specimen_count_list = array();
		foreach($specimen_type_list as $specimen_type_id)
		{
			$specimen_count_list[] = get_specimen_count($lab_config, $specimen_type_id, $date_from, $date_to);
		}
		for($i = 0; $i < count($specimen_type_list); $i++)
		{
			# Add to return value if specimen count not zero
				if($specimen_count_list[$i] != 0)
			$retval[$specimen_type_list[$i]] = $specimen_count_list[$i];
		}
		DbUtil::switchRestore($saved_db);
		return $retval;
	}

	public static function getDiscreteInfectionStats($lab_config, $date_from, $date_to, $test_type_id_passed = null)
	{
		# Fetch all test types with one measure having discrete P/N range
		$retval = array();
		if( !$test_type_id_passed )
			$test_type_list = get_discrete_value_test_types($lab_config);
		else
			$test_type_list[] = $test_type_id_passed;
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		# For each test type, fetch negative records
		foreach($test_type_list as $test_type_id)
		{
			$query_string =
				"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
				"AND (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";
			$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];
			$query_string =
				"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND result!=''".
				"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' )";
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
			# If total tests is 0, ignore
			if($count_all == 0)
				continue;

			$query_string =
					"SELECT prevalence_threshold FROM test_type ".
					"WHERE test_type_id=$test_type_id ";
			$record = query_associative_one($query_string);
			$threshold = $record['prevalence_threshold'];
			$retval[$test_type_id] = array($count_all, $count_negative, $threshold);
		}
		DbUtil::switchRestore($saved_db);
		return $retval;
	}

	public static function getDiscreteInfectionStatsAggregate($lab_config_id, $date_from, $date_to, $test_type_id) {
		$retval = array();

		#All Tests & All Labs
		if( $test_type_id == 0 && $lab_config_id == 0 ) {
			$site_list = get_site_list($_SESSION['user_id']);

			$userId = $_SESSION['user_id'];
			$saved_db = DbUtil::switchToGlobal();
			$query = "SELECT * FROM test_mapping WHERE user_id = $userId";
			$resultset = query_associative_all($query);
			foreach($resultset as $record) {
					$labIdTestIds = explode(';',$record['lab_id_test_id']);
					foreach($labIdTestIds as $labIdTestId) {
						$labIdTestId = explode(':',$labIdTestId);
						$labId = $labIdTestId[0];
						$testId= $labIdTestId[1];
						$test_type_list_all[$labId][] = $testId;
						$test_type_names[$labId][] = $record['test_name'];
					}
			}
			DbUtil::switchRestore($saved_db);
			foreach( $site_list as $key => $value) {

				$lab_config = LabConfig::getById($key);
				$test_type_list = array();
				$test_type_list = $test_type_list_all[$key];
				$testNames = $test_type_names[$key];
				$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				$testCount = -1;

				# For each test type, fetch negative records
				foreach($test_type_list as $test_type_id)
				{
					$query_string =
						"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
						"WHERE t.test_type_id=$test_type_id ".
						"AND t.specimen_id=s.specimen_id ".
						"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
						"AND (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";
					$record = query_associative_one($query_string);
					$count_negative = intval($record['count_val']);
					$query_string =
						"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
						"WHERE t.test_type_id=$test_type_id ".
						"AND t.specimen_id=s.specimen_id ".
						"AND result!=''".
						"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' )";
					$record = query_associative_one($query_string);
					$count_all = intval($record['count_val']);
					$testCount++;
					# If total tests is 0, ignore
					if( $count_all == 0 )
						continue;
					$testName = $testNames[$testCount];

					if( !array_key_exists($testName, $retval) ) {
						$retval[$testName] = array($count_all, $count_negative);
					}
					else {
						$count_all += intval($retval[$testName][0]);
						$count_negative += intval($retval[$testName][1]);
						$retval[$testName] = array( $count_all, $count_negative);
					}
				}
			}
			DbUtil::switchRestore($saved_db);
			return $retval;
		}
		# All Tests for a Single Lab
		else if ( $test_type_id == 0 && count($lab_config_id) == 1 ) {
			$lab_config = LabConfig::getById($lab_config_id[0]);
			$retvalues = StatsLib::getDiscreteInfectionStats($lab_config, $date_from, $date_to);
			$saved_db = DbUtil::switchToLabConfig($lab_config->id);
			foreach( $retvalues as $key => $value ) {
				$testName = get_test_name_by_id($key);
				$retval[$testName] = $value;
			}
			DbUtil::switchRestore($saved_db);
			return $retval;
		}
		# All Tests for more than one lab
		else if ( $test_type_id == 0 && count($lab_config_id) > 1 ) {
			$userId = $_SESSION['user_id'];
			$saved_db = DbUtil::switchToGlobal();
			$query = "SELECT * FROM test_mapping WHERE user_id = $userId";
			$resultset = query_associative_all($query);
			foreach($resultset as $record) {
					$labIdTestIds = explode(';',$record['lab_id_test_id']);
					foreach($labIdTestIds as $labIdTestId) {
						$labIdTestId = explode(':',$labIdTestId);
						$labId = $labIdTestId[0];
						$testId= $labIdTestId[1];
						$test_type_list_all[$labId][] = $testId;
						$test_type_names[$labId][] = $record['test_name'];
					}
			}
			DbUtil::switchRestore($saved_db);

			foreach( $lab_config_id as $key) {
				$lab_config = LabConfig::getById($key);
				$test_type_list = array();
				$test_type_list = $test_type_list_all[$key];
				$testNames = $test_type_names[$key];
				$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				$testCount = -1;

				# For each test type, fetch negative records
				foreach($test_type_list as $test_type_id)
				{
					$query_string =
						"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
						"WHERE t.test_type_id=$test_type_id ".
						"AND t.specimen_id=s.specimen_id ".
						"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
						"AND (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";
					$record = query_associative_one($query_string);
					$count_negative = intval($record['count_val']);
					$query_string =
						"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
						"WHERE t.test_type_id=$test_type_id ".
						"AND t.specimen_id=s.specimen_id ".
						"AND result!=''".
						"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' )";
						//echo($query_string);
					$record = query_associative_one($query_string);
					$count_all = intval($record['count_val']);
					$testCount++;
					# If total tests is 0, ignore
					if($count_all == 0)
						continue;
					$testName = $testNames[$testCount];

					if( !array_key_exists($testName, $retval) ) {
						$retval[$testName] = array($count_all, $count_negative);
					}
					else {
						$count_all += intval($retval[$testName][0]);
						$count_negative += intval($retval[$testName][1]);
						$retval[$testName] = array( $count_all, $count_negative);
					}
				}
			}
			DbUtil::switchRestore($saved_db);
			return $retval;
		}
		else {
			/* Build Array Map with Lab Id as Key and Test Id as corresponding Value */
			$labIdTestIds = explode(";",$test_type_id);
			$testIds = array();
			foreach( $labIdTestIds as $labIdTestId) {
					$labIdTestIdsSeparated = explode(":",$labIdTestId);
					$labId = $labIdTestIdsSeparated[0];
					$testId = $labIdTestIdsSeparated[1];
					$testIds[$labId] = $testId;
			}

			# Particular Test & All Labs
			if ( $test_type_id != 0 && $lab_config_id == 0 ) {
				$site_list = get_site_list($_SESSION['user_id']);

				foreach( $site_list as $key => $value) {
					$lab_config = LabConfig::getById($key);
					$saved_db = DbUtil::switchToLabConfig($lab_config->id);
					$test_type_id = $testIds[$lab_config->id];

					# For particular test type, fetch negative records

						$query_string =
							"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
							"WHERE t.test_type_id=$test_type_id ".
							"AND t.specimen_id=s.specimen_id ".
							"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
							"AND (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";
						$record = query_associative_one($query_string);
						$count_negative = intval($record['count_val']);
						$query_string =
							"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
							"WHERE t.test_type_id=$test_type_id ".
							"AND t.specimen_id=s.specimen_id ".
							"AND result!=''".
							"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' )";
						$record = query_associative_one($query_string);
						$count_all = intval($record['count_val']);
						# If total tests is 0, ignore
						if($count_all == 0)
							continue;
						$testName = get_test_name_by_id($test_type_id);

						$labName = $lab_config->name;
						$retval[$labName] = array( $count_all, $count_negative);
				}
				DbUtil::switchRestore($saved_db);
				return $retval;
			}
			# Particular Test for Single Lab
			else if ( $test_type_id != 0 && count($lab_config_id) == 1 ) {

				$lab_config = LabConfig::getById($lab_config_id[0]);
				$test_type_id = $testIds[$lab_config->id];
				$retvalues = StatsLib::getDiscreteInfectionStats($lab_config, $date_from, $date_to, $test_type_id);
				$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				foreach( $retvalues as $key => $value ) {
					$testName = get_test_name_by_id($key);
					$retval[$testName] = $value;
				}
				DbUtil::switchRestore($saved_db);
				return $retval;
			}
			# Particular Test & Multiple Labs
			else if ( $lab_config_id != 0 && $test_type_id != 0 ) {
				foreach( $lab_config_id as $key) {
					$lab_config = LabConfig::getById($key);
					$test_type_id = $testIds[$lab_config->id];
					$saved_db = DbUtil::switchToLabConfig($lab_config->id);

					# For particular test type, fetch negative records

						$query_string =
							"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
							"WHERE t.test_type_id=$test_type_id ".
							"AND t.specimen_id=s.specimen_id ".
							"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
							"AND (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";
						$record = query_associative_one($query_string);
						$count_negative = intval($record['count_val']);
						$query_string =
							"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
							"WHERE t.test_type_id=$test_type_id ".
							"AND t.specimen_id=s.specimen_id ".
							"AND result!=''".
							"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' )";
							//echo($query_string);
						$record = query_associative_one($query_string);
						$count_all = intval($record['count_val']);
						# If total tests is 0, ignore
						if($count_all == 0)
							continue;
						$testName = get_test_name_by_id($test_type_id);
						$labName = $lab_config->name;
						$query_string =
							"SELECT prevalence_threshold FROM test_type ".
							"WHERE test_type_id=$test_type_id ";
						$record = query_associative_one($query_string);
						$threshold = intval($record['prevalence_threshold']);

						$retval[$labName] = array( $count_all, $count_negative, $threshold );
				}
				DbUtil::switchRestore($saved_db);
				return $retval;
			}
		}
	}

	public static function getDiscreteInfectionStatsWeekly($lab_config,$test_type_id, $date_from, $date_to, $gender=null)
	{
		$i=7;
		# Fetch all test types with one measure having discrete P/N range
		$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$date_from_parts = explode("-", $date_from);
		$date_to_parts = explode("-", $date_to);
		$date_ts = mktime(0, 0, 0, $date_from_parts[1], $date_from_parts[2], $date_from_parts[0]);
		$date_to_ts=mktime(0, 0, 0, $date_to_parts[1], $date_to_parts[2], $date_to_parts[0]);

		while($date_ts<=$date_to_ts) {
			$second_day_ts= mktime(0, 0, 0, $date_from_parts[1],$date_from_parts[2]+$i,$date_from_parts[0]);
			$date_fromp=date("Y-m-d", $date_ts);
			$date_top=date("Y-m-d", $second_day_ts);

			if($gender=='M'||$gender=='F') {
				$query_string =
						"SELECT COUNT(*) AS count_val  FROM test t, patient p, specimen s ".
						"WHERE t.test_type_id=$test_type_id ".
						"AND p.patient_id=s.patient_id ".
						"AND p.sex LIKE '$gender' ".
						"AND t.specimen_id=s.specimen_id ".
						"AND (s.date_collected BETWEEN '$date_fromp' AND '$date_top') ".
						"AND  (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')";
			}
			else {
				$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )".
				"AND  (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";

				}
			$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];

			$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND result!=''".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )";
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
			/*if($count_all != 0)*/
				$retval[$date_ts] = array($count_all, $count_negative);

		$date_ts = $second_day_ts;
		$i=$i+7;



	}
		DbUtil::switchRestore($saved_db);

		return $retval;
	}
public static function getDiscreteInfectionStatsG($lab_config,$test_type_id, $date_from,$date_to,$type) {

		$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		if($type=='w') {

				$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";

				$resultset1= query_associative_all($query_string1);
				}
		else if($type=='d') {
			$query_string =
				"SELECT s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT s.date_collected AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";

			$resultset1= query_associative_all($query_string1);
		}
		else {
			$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), month(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected),month(s.date_collected)";

				$resultset1= query_associative_all($query_string1);

				}

$record_total= array();
$record_neg=array();
		foreach($resultset1 as $record) {
			$week=$record['week'];
			$record_neg[$week]=$record['count_val'];
		}

		foreach($resultset as $record) {
			$week=$record['week'];
			$count++;
			$record_total[$week]=$record['count_val'];
		}

		$counter=0;

		foreach($record_total as $key=>$value) {

			if($record_neg[$key]=="")
				$count_negative=0;
			else
				$count_negative=$record_neg[$key];

			$count_all=$value;
			$date_from_parts = explode("-", $key);
			$date_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,$date_from_parts[2],$date_from_parts[0]);
			$retval[$counter] = array($count_all, $count_negative, $date_ts);
			$counter++;
		}

		DbUtil::switchRestore($saved_db);

		return $retval;
}
public static function getDiscreteInfectionStatsGenderM($lab_config,$test_type_id, $date_from,$date_to,$type)
{

$gender='M';
$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				if($type=='w')
				{
				$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";

				$resultset1= query_associative_all($query_string1);
				}
				else if($type=='d')
				{
				$query_string =
				"SELECT s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";

				$resultset1= query_associative_all($query_string1);
				}
				else
				{
				$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), month(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected),month(s.date_collected)";

				$resultset1= query_associative_all($query_string1);

				}

$record_total= array();
$record_neg=array();
foreach($resultset1 as $record)
{
$week=$record['week'];
$record_neg[$week]=$record['count_val'];
}

foreach($resultset as $record)
{
$week=$record['week'];
$count++;
$record_total[$week]=$record['count_val'];
}



$counter=0;

foreach($record_total as $key=>$value)
{
if($record_neg[$key]=="")
$count_negative=0;
else
$count_negative=$record_neg[$key];
$count_all=$value;
$date_from_parts = explode("-", $key);
$date_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,$date_from_parts[2],$date_from_parts[0]);
$retval[$counter] = array($count_all, $count_negative, $date_ts);
$counter++;
}
	/*	$i=1;
		if($type=='w')
			$i=7;

		# Fetch all test types with one measure having discrete P/N range
		$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$date_from_parts = explode("-", $date_from);
		$date_to_parts = explode("-", $date_to);
		$date_ts = mktime(0, 0, 0, $date_from_parts[1], $date_from_parts[2], $date_from_parts[0]);
		$date_to_ts=mktime(0, 0, 0, $date_to_parts[1], $date_to_parts[2], $date_to_parts[0]);

		while($date_ts<$date_to_ts)
	{
		if($type=='m')
		$end_date_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,0,$date_from_parts[0]);
		else
		$end_date_ts= mktime(0, 0, 0, $date_from_parts[1],$date_from_parts[2]+$i,$date_from_parts[0]);
		$date_fromp=date("Y-m-d", $date_ts);
		$date_top=date("Y-m-d", $end_date_ts);

$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, patient p, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (s.date_collected BETWEEN '$date_fromp' AND '$date_top') ".
				"AND  (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')";


			$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];

			$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.sex= '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!='' ".
				"AND p.patient_id=s.patient_id ".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )";
				//echo($query_string);
			$count_all = $record['count_val'];
						if($count_all != 0)
				$retval[$date_ts] = array($count_all, $count_negative);
				$date_ts = $end_date_ts;
			if($type=='w')
				$i=$i+7;
			else
				$i++;



	}*/
		DbUtil::switchRestore($saved_db);

		return $retval;

}
	public static function getDiscreteInfectionStatsGenderF($lab_config,$test_type_id,$date_from,$date_to,$type)
	{
	$gender='F';
		/*$i=1;
		if($type=='w')
			$i=7;

		# Fetch all test types with one measure having discrete P/N range

		$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$date_from_parts = explode("-", $date_from);
		$date_to_parts = explode("-", $date_to);
		$date_ts = mktime(0, 0, 0, $date_from_parts[1], $date_from_parts[2], $date_from_parts[0]);
		$date_to_ts=mktime(0, 0, 0, $date_to_parts[1], $date_to_parts[2], $date_to_parts[0]);

		while($date_ts<$date_to_ts)
	{
		if($type=='m')
		$end_date_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,0,$date_from_parts[0]);
		else
		$end_date_ts= mktime(0, 0, 0, $date_from_parts[1],$date_from_parts[2]+$i,$date_from_parts[0]);
		$date_fromp=date("Y-m-d", $date_ts);
		$date_top=date("Y-m-d", $end_date_ts);

$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, patient p, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex = '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (s.date_collected BETWEEN '$date_fromp' AND '$date_top') ".
				"AND  (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')";

					$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];

			$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t,patient p, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.sex = '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND p.patient_id=s.patient_id ".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )";
			//echo($query_string);
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
						if($count_all != 0)
				$retval[$date_ts] = array($count_all, $count_negative);

		$date_ts = $end_date_ts;
		if($type=='w')
		$i=$i+7;
		else
		$i++;



	}*/
	$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				if($type=='w')
				{
				$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), week(s.date_collected)";

				$resultset1= query_associative_all($query_string1);
				}
				else if($type=='d')
				{
				$query_string =
				"SELECT s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND t.result!=''".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY s.date_collected";

				$resultset1= query_associative_all($query_string1);
				}
				else
				{
				$query_string =
				"SELECT year(s.date_collected), s.date_collected  AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND t.result!=''".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected), month(s.date_collected)";
			$resultset = query_associative_all($query_string);
			$query_string1=
				"SELECT year(s.date_collected), s.date_collected AS week ,COUNT(*) AS count_val  FROM test t, patient p,specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex= '$gender' ".
				"AND (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')".
				"AND (s.date_collected BETWEEN '$date_from' AND '$date_to') ".
				 "GROUP BY year(s.date_collected),month(s.date_collected)";

				$resultset1= query_associative_all($query_string1);

				}

$record_total= array();
$record_neg=array();
foreach($resultset1 as $record)
{
$week=$record['week'];
$record_neg[$week]=$record['count_val'];
}

foreach($resultset as $record)
{
$week=$record['week'];
$count++;
$record_total[$week]=$record['count_val'];
}



$counter=0;

foreach($record_total as $key=>$value)
{
if($record_neg[$key]=="")
$count_negative=0;
else
$count_negative=$record_neg[$key];
$count_all=$value;
$date_from_parts = explode("-", $key);
$date_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,$date_from_parts[2],$date_from_parts[0]);
$retval[$counter] = array($count_all, $count_negative, $date_ts);
$counter++;
}
		DbUtil::switchRestore($saved_db);

		return $retval;



	}
	public static function getDiscreteInfectionStatsDaily($lab_config,$test_type_id, $date_from, $date_to, $gender=null)
	{
			$i=1;
		# Fetch all test types with one measure having discrete P/N range
		$retval = array();
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$date_from_parts = explode("-", $date_from);
		$date_to_parts = explode("-", $date_to);
		$date_ts = mktime(0, 0, 0, $date_from_parts[1], $date_from_parts[2], $date_from_parts[0]);
		$date_to_ts=mktime(0, 0, 0, $date_to_parts[1], $date_to_parts[2], $date_to_parts[0]);

		while($date_ts<$date_to_ts)
	{
		$second_day_ts= mktime(0, 0, 0, $date_from_parts[1],$date_from_parts[2]+$i,$date_from_parts[0]);
		$date_fromp=date("Y-m-d", $date_ts);
		$date_top=date("Y-m-d", $second_day_ts);
	if($gender=='M'||$gender=='F')
	{
$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, patient p, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex LIKE '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (s.date_collected BETWEEN '$date_fromp' AND '$date_top') ".
				"AND  (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')";

				}
				else
{

$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )".
				"AND  (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";

				}

	$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];

			$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND result!=''".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )";
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
						if($count_all != 0)
				$retval[$date_ts] = array($count_all, $count_negative);

		$date_ts = $second_day_ts;
		$i++;



	}
		DbUtil::switchRestore($saved_db);

		return $retval;
	}



		public static function getDiscreteInfectionStatsMonthly($lab_config,$test_type_id, $date_from, $date_to, $gender=null)
	{
		$i=1;


		# Fetch all test types with one measure having discrete P/N range
		$retval = array();

		$saved_db = DbUtil::switchToLabConfig($lab_config->id);


		$date_from_parts = explode("-", $date_from);

		$date_to_parts = explode("-", $date_to);

		$month_ts = mktime(0, 0, 0, $date_from_parts[1], 0, $date_from_parts[0]);
		$date_to_ts=mktime(0, 0, 0, $date_to_parts[1], 0, $date_to_parts[0]);
		# For the test type, fetch negative records

		while($month_ts<$date_to_ts)
	{
	$end_of_month_ts= mktime(0, 0, 0, $date_from_parts[1]+$i,0,$date_from_parts[0]);
	$date_fromp=date("Y-m-d", $month_ts);
	$date_top=date("Y-m-d", $end_of_month_ts);

				if($gender=='M'||$gender=='F')
	{
$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, patient p, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND p.patient_id=s.patient_id ".
				"AND p.sex LIKE '$gender' ".
				"AND t.specimen_id=s.specimen_id ".
				"AND (s.date_collected BETWEEN '$date_fromp' AND '$date_top') ".
				"AND  (t.result LIKE 'N,%' OR t.result LIKE 'n�gatif,%' OR t.result LIKE 'negatif,%' OR t.result LIKE 'n,%' OR t.result LIKE 'negative,%')";

				}
				else
			{

$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )".
				"AND  (result LIKE 'N,%' OR result LIKE 'n�gatif,%' OR result LIKE 'negatif,%' OR result LIKE 'n,%' OR result LIKE 'negative,%')";

				}
			$record = query_associative_one($query_string);
			$count_negative = $record['count_val'];

			$query_string =
				"SELECT COUNT(*) AS count_val  FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND result!=''".
				"AND ( s.date_collected BETWEEN '$date_fromp' AND '$date_top' )";
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
				# If total tests is 0, ignore
			if($count_all != 0)
		$retval[$month_ts] = array($count_all, $count_negative);


		$month_ts = $end_of_month_ts;
		$i++;



	}
		DbUtil::switchRestore($saved_db);
		return $retval;
	}

	public static function getRangeInfectionStats($lab_config, $date_from, $date_to)
	{
		$test_type_list = get_range_value_test_types($lab_config);
		# For each test type, fetch all measures
		# For each measure, create distribution based on range
		foreach($test_type_list as $test_type_id)
		{
			# Collect measure(s) information
			$measure_list = get_test_type_measures($test_type_id);
			$measure_meta_list = array();
			foreach($measure_list as $measure_id)
			{
				$measure = get_measure_by_id($measure_id);
				$measure_meta = new MeasureMeta();
				$measure_meta->name = $measure->getName();
				$measure_meta->countParts = array();
				$range = $measure->range;

				if(strpos($range, ":") === false)
				{
					# Discrete value range
					$range_options = explode("#", $range);
					$measure_meta->rangeType = MeasureMeta::$DISCRETE;
					$measure_meta->rangeValues = $range_options;
				}
				else
				{
					# Continuous value range
					$range_bounds = explode(":", $range);
					$measure_meta->rangeType = MeasureMeta::$CONTINUOUS;
					$measure_meta->rangeValues = $range_bounds;
				}
				$measure_meta_list[] = $measure_meta;
			}

			# Calculate stats
			$query_string =
				"SELECT COUNT(*) AS count_val FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
				"AND t.result <> ''";
			$record = query_associative_one($query_string);
			$count_all = $record['count_val'];
			# Fetch result values
			$query_string =
				"SELECT t.result FROM test t, specimen s ".
				"WHERE t.test_type_id=$test_type_id ".
				"AND t.specimen_id=s.specimen_id ".
				"AND ( s.date_collected BETWEEN '$date_from' AND '$date_to' ) ".
				"AND t.result <> ''";
			$resultset = query_associative_all($query_string);
			foreach($resultset as $record)
			{
				$result_string = substr($record['result'], 0, -1);
				$result_list = explode(",", $result_string);
				for($i = 0; $i < count($result_list); $i++)
				{
					$measure_meta = $measure_meta_list[$i];
					if($measure_meta->rangeType == MeasureMeta::$CONTINUOUS)
					{
						$range_bounds = $measure_meta->rangeValues;
						$interval = $range_bounds[1] - $range_bounds[0];
						$base = $interval / 10;
						$offset = $result_list[$i] - $range_bounds[0];
						$bucket = $offset / $base;
						echo $bucket;
						break;
					}
				}
			}
		}
	}

	#
	# Disease Report related functions
	# Called from report_disease.php
	#

	public static function getDiseaseTotal($lab_config, $test_type, $date_from, $date_to)
	{
		# Returns the total number of tests performed during the given date range
		$query_string =
			"SELECT COUNT(*) AS val FROM test t, specimen sp ".
			"WHERE t.test_type_id=$test_type->testTypeId ".
			"AND t.specimen_id=sp.specimen_id ".
			"AND t.result <> '' ".
			"AND (sp.date_collected BETWEEN '$date_from' AND '$date_to')";
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$resultset = query_associative_all($query_string);
		DbUtil::switchRestore($saved_db);
		return $resultset[0]['val'];
	}

	public static function setDiseaseSetList($lab_config, $test_type, $date_from, $date_to, $multipleCount = 0,$site_list,$add_site_condition)
	{
		# Initializes diseaseSetList for capturing params
		if($multipleCount == 0)
			StatsLib::$diseaseSetList = array();
		$query_string =
			"SELECT t.result AS result_value, ".
			"p.sex AS patient_gender, ".
			"p.patient_id AS patient_id ".
			"FROM test t, specimen sp, patient p ".
			"WHERE t.specimen_id=sp.specimen_id ".
			"AND t.result <> '' ".
			"AND t.test_type_id=$test_type->testTypeId ".
			"AND (sp.date_collected BETWEEN '$date_from' AND '$date_to') ".
			"AND sp.patient_id=p.patient_id";
if($add_site_condition==true)
$query_string=$query_string." and sp.site_id in (".$site_list.")";
		$saved_db = DbUtil::switchToLabConfig($lab_config->id);
		$resultset = query_associative_all($query_string);
		$measure_list = $test_type->getMeasureIds();
		if(count($resultset) == 0 || $resultset == null) {
			DbUtil::switchRestore($saved_db);
			return;
		}
		foreach($resultset as $record)
		{
			$patient_id = $record['patient_id'];
			$patient = Patient::getById($patient_id);
			$current_set = new DiseaseSet();
			$current_set->resultValues = array();
			$result_csv_parts = explode(",", $record['result_value']);
			# Build assoc list for result values
			## Format: resultValues[measure_id] = result_value
			for($i = 0; $i < count($measure_list); $i++)
			{
				$result_part = $result_csv_parts[$i];
				if(trim($result_part) == "")
					continue;
				$curr_measure_id = $measure_list[$i];
				$current_set->resultValues[$curr_measure_id] = $result_part;
			}
			$current_set->patientGender = $record['patient_gender'];
			$current_set->patientAge = $patient->getAgeNumber();
			# Append to diseaseSetList
			StatsLib::$diseaseSetList[] = $current_set;
		}
		DbUtil::switchRestore($saved_db);
	}

	public static function setDiseaseSetListAggregate($lab_config_ids, $test_type, $date_from, $date_to)
	{
		$testIds = array();
		$labIdTestIds = explode(";",$test_type->labIdTestId);
		foreach( $labIdTestIds as $labIdTestId) {
				$labIdTestIdsSeparated = explode(":",$labIdTestId);
				$labId = $labIdTestIdsSeparated[0];
				$testId = $labIdTestIdsSeparated[1];
				$testIds[$labId] = $testId;
		}
		$labCount = 0;
		/* All Tests for All Labs */
		if ( count($lab_config_ids) == 1 && $lab_config_ids == 0 ) {
			$site_list = get_site_list($_SESSION['user_id']);

			foreach( $site_list as $key => $value) {
				$lab_config = get_lab_config_by_id($key);
				$labName = $lab_config->name;
				$namesArray[] = $labName;
				$test_type_id = $testIds[$lab_config->id];
				$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				$test = TestType::getById($test_type_id);
				StatsLib::setDiseaseSetList($lab_config, $test, $date_from, $date_to, $labCount);
				$labCount++;
				DbUtil::switchRestore($saved_db);
			}
		}
		/* All Tests for Single Lab */
		else if ( count($lab_config_ids) == 1 ) {
			$lab_config = get_lab_config_by_id($lab_config_ids[0]);
			$labName = $lab_config->name;
			$namesArray[] = $labName;
			$test_type_id = $testIds[$lab_config->id];
			$saved_db = DbUtil::switchToLabConfig($lab_config->id);
			$test = TestType::getById($test_type_id);
			StatsLib::setDiseaseSetList($lab_config, $test, $date_from, $date_to, $labCount);
			$labCount++;
			DbUtil::switchRestore($saved_db);
		}
		/* All Tests for Multiple Labs */
		else if ( count($lab_config_ids) > 1 ) {
			foreach( $lab_config_ids as $key) {
				$lab_config = get_lab_config_by_id($key);
				$labName = $lab_config->name;
				$namesArray[] = $labName;
				$test_type_id = $testIds[$lab_config->id];
				$saved_db = DbUtil::switchToLabConfig($lab_config->id);
				$test = TestType::getById($test_type_id);
				StatsLib::setDiseaseSetList($lab_config, $test, $date_from, $date_to, $labCount);
				$labCount++;
				DbUtil::switchRestore($saved_db);
			}
		}
	}

	public static function getDiseaseFilterCountAggregate($disease_filter, $globalTestType, $currentMeasurecount, $lab_config_ids) {
		# Returns total number of records matching filter criteria
		$retval = 0;
		$testIds = array();
		$labIdTestIds = explode(";",$globalTestType->labIdTestId);
		foreach( $labIdTestIds as $labIdTestId ) {
				$labIdTestIdsSeparated = explode(":",$labIdTestId);
				$labId = $labIdTestIdsSeparated[0];
				$testId = $labIdTestIdsSeparated[1];
				$testIds[$labId] = $testId;
		}
		if ( count($lab_config_ids) == 1 && $lab_config_ids == 0 ) {
			$site_list = get_site_list($_SESSION['user_id']);
			foreach( $site_list as $key => $value) {
				$currentMeasureCount = 0;
				$lab_config = get_lab_config_by_id($key);
				$measureId = getLabMeasureIdFromGlobalMeasureId($lab_config->id, $globalTestType, $currentMeasureCount);
				$disease_filter->measureId = $measureId;
				$labName = $lab_config->name;
				$namesArray[] = $labName;
				$retval += StatsLib::getDiseaseFilterCount($disease_filter);
			}
			getDiseaseFilterCount($disease_filter);
		}
		/* All Tests for Single Lab */
		else if ( count($lab_config_ids) == 1 ) {
			$lab_config = get_lab_config_by_id($lab_config_ids[0]);
			$measureId = getLabMeasureIdFromGlobalMeasureId($lab_config->id, $globalTestType, $currentMeasureCount);
			$disease_filter->measureId = $measureId;
			$labName = $lab_config->name;
			$namesArray[] = $labName;
			$retval = StatsLib::getDiseaseFilterCount($disease_filter);
		}
		/* All Tests for Multiple Labs */
		else if ( count($lab_config_ids) > 1 ) {
			foreach( $lab_config_ids as $key) {
				$currentMeasureCount = 0;
				$lab_config = LabConfig::getById($key);
				$measureId = getLabMeasureIdFromGlobalMeasureId($lab_config->id, $globalTestType, $currentMeasureCount);
				$disease_filter->measureId = $measureId;
				$labName = $lab_config->name;
				$namesArray[] = $labName;
				$retval += StatsLib::getDiseaseFilterCount($disease_filter);
			}
		}
		return $retval;
	}

	public static function getDiseaseFilterCount($disease_filter)
	{
		# Returns total number of records matching filter criteria
		$retval = 0;
		//print_r($disease_filter);
		foreach(StatsLib::$diseaseSetList as $disease_set)
		{
			# Flag to track if gender and/or age slots have matched for this entry.
			$has_match = false;
			# Check if age falls in range
			if
			(
				(
					# No upper bound on range: Only check if age is >= lower bound.
					(trim($disease_filter->patientAgeRange[1]) == "+") &&
					($disease_set->patientAge >= $disease_filter->patientAgeRange[0])
				)
				||
				(
					($disease_set->patientAge >= $disease_filter->patientAgeRange[0]) &&
					($disease_set->patientAge < $disease_filter->patientAgeRange[1])
				)
			)
			{
				# Age falls in range
				# Check if result value matches or is in range
				if($disease_filter->rangeType == DiseaseSetFilter::$DISCRETE)
				{
					if
					(
						(isset($disease_set->resultValues[$disease_filter->measureId])) &&
						($disease_set->resultValues[$disease_filter->measureId] === $disease_filter->rangeValues)
					)
					{

						# Do nothing
						$has_match = true;
					}
					else
					{
						continue;
					}
				}
				else if($disease_filter->rangeType == DiseaseSetFilter::$CONTINUOUS)
				{
					if
					(
						(isset($disease_set->resultValues[$disease_filter->measureId])) &&
						($disease_set->resultValues[$disease_filter->measureId] >= $disease_filter->rangeValues[0]) &&
						($disease_set->resultValues[$disease_filter->measureId] < $disease_filter->rangeValues[1])
					)
					{
						# Do nothing
						$has_match = true;
					}
					else
					{
						continue;
					}
				}
				if($has_match === true)
				{
					# Age and/or gender slot have matched:
					# Check gender if present in filter
					if($disease_filter->patientGender == null)
					{
						# Gender no specified. Increment match count.
						$retval++;
					}
					else
					{
						# Check if gender is the same as in filter.
						if(strcmp(strtolower($disease_set->patientGender), strtolower($disease_filter->patientGender)) == 0)
						{
							$retval++;
						}
					}
				}
			}
		}
		return $retval;
	}
}
?>
