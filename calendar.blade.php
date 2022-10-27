<?php

function build_calender($month, $year,$veterinarian){
      
    $mysqli=new mysqli('localhost','root','','bookingcalendar');

    $stmt=$mysqli->prepare("SELECT * FROM veterinarian");
    $veterinarians="";
    $veterinarians.="<option>Select Doctor</option>";
    $first_veterinarian=0;
    $i=0;
    if($stmt->execute()){
        $result=$stmt->get_result();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                if($i==0){
                    $first_veterinarian=$row['veterinarian_id'];
                }
                $veterinarians.="<option value='".$row['veterinarian_id']."'>".$row['veterinarian_name']."</option>";
                $i++;
            }
            $stmt -> close();
        }
    }
    if($veterinarian!=0){
        $first_veterinarian=$veterinarian;
    }

    $stmt=$mysqli->prepare("SELECT * FROM bookings WHERE date=? AND YEAR(date)=? AND veterinarian_id=?");
    $stmt->bind_param('ssi', $date,$year,$first_veterinarian);
    $bookings=array();
    if($stmt->execute()){
        $result=$stmt->get_result();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $bookings=$row['date'];
            }

            $stmt -> close();
        }
    }

    $daysOfWeek = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');

    $firstDayOfMonth = mktime(0,0,0,$month,1,$year);

    $numberDays=date('t',$firstDayOfMonth);

    $dateComponents=getdate($firstDayOfMonth);

    $monthName = $dateComponents['month'];

    $dayOfWeek = $dateComponents['wday'];

    if($dayOfWeek == 0){
        $dayOfWeek = 6;
    }
    else{
        $dayOfWeek = $dayOfWeek - 1;
    }

    $datetoday = date('Y-m-d');
    
    $calendar ="<table class='table table-bordered'>";
    $calendar .="<center><h2>$monthName $year</h2>";
    $calendar .="<a class='btn btn-xs btn-primary' href='?month=".date('m', mktime(0, 0, 0, $month-1, 1, $year))."&year=".date('Y', mktime(0, 0, 0, $month-1, 1, $year))."'>Previous Month</a>";
    $calendar .="<a class='btn btn-xs btn-primary' href='?month=".date('m')."&year=".date('Y')."'>Current Month</a>";
    $calendar .="<a class='btn btn-xs btn-primary' href='?month=".date('m',mktime(0,0,0,$month+1,1,$year))."&year=".date('Y',mktime(0,0,0,$month+1,1,$year))."'>Next Month</a></center><br>";

    $calendar.="
    <form id='veterinarian_select_form'>
    <div class='row'>
        <div class='col-md-6 col-md-offset-3 form-group'>
        <label>Select Doctor</label>
            <select class='form-control' id='veterinarian_select' name='veterinarian'>
                ".$veterinarians."
            </select>
            <input type='hidden' name='month' value='".$month."' />
            <input type='hidden' name='year' value='".$year."' />
        </div>
    </div>

    </form>
    
    <table class='table table-bordered'>";

    $stmt=$mysqli->prepare("SELECT * FROM veterinarian WHERE veterinarian_id=?");
    $stmt->bind_param('i',$first_veterinarian);
    $bookings=array();
    if($stmt->execute()){
        $result=$stmt->get_result();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $veterinarian_name=$row['veterinarian_name'];
            }

            $stmt -> close();
        }
    }

    $calendar.="Doctor: $veterinarian_name";
    $calendar .="<tr>";

    

    foreach ($daysOfWeek as $day){
        $calendar .="<th class='header'>$day</th>";
    }
    
    $calendar .="</tr><tr>";

    if ($dayOfWeek > 0){
        for ($k=0;$k<$dayOfWeek;$k++){
            $calendar .="<td></td>";
        }
    }
    
    $currentDay = 1;

    $month=str_pad($month,2,"0",STR_PAD_LEFT);

    while ($currentDay <= $numberDays){
       
        if ($dayOfWeek == 7){
            $dayOfWeek = 0;
            $calendar .="</tr><tr>";
        }
        $currentDayRel=str_pad($currentDay,2,"0",STR_PAD_LEFT);
        $date="$year-$month-$currentDayRel";

        $dayname=strtolower(date("l",strtotime($date)));
        $eventNum=0;
        $today=$date==date('Y-m-d')?"today":"";

        if($dayname=='saturday' || $dayname=='sunday'){
            $calendar .="<td><h4>$currentDay</h4><button class='btn btn-danger btn-xs'>HOLIDAY</button>";
        }
        elseif($date < date('Y-m-d')){
            $calendar .="<td><h4>$currentDay</h4><button class='btn btn-danger btn-xs'>N/A</button>";
        }
        // elseif(in_array($date, $bookings)){
        //     $calendar .="<td class='$today'><h4>$currentDay</h4><button class='btn btn-danger btn-xs'>Already Booked</button>";
        // }
        else{
            $totalbookings=checkSlots($mysqli,$date,$year,$first_veterinarian);

            if($totalbookings==8){
                $calendar .="<td class='$today'><h4>$currentDay</h4><a href='#' class='btn btn-danger btn-xs'>All Booked</a>";
            }
            else{
                $availabelslots=8-$totalbookings;
                $calendar .="<td class='$today'><h4>$currentDay</h4><a href='book.php?date=".$date."&veterinarian_id=".$first_veterinarian."' class='btn btn-success btn-xs'>Book</a><small><i>$availabelslots slots left</i></small>";
            }  
        }

        $calendar .="</td>";

        $currentDay++;
        $dayOfWeek++;
    }

    if ($dayOfWeek != 7){
        $remainingDays = 7 - $dayOfWeek;
        for($i = 0;$i<$remainingDays;$i++){
            $calendar .="<td></td>";
        }
    }

    $calendar .="</tr>";
    $calendar .="</table>";

    echo $calendar;

}

function checkSlots($mysqli,$date,$year,$first_veterinarian){

    $stmt=$mysqli->prepare("SELECT * FROM bookings WHERE date=? AND YEAR(date)=? AND veterinarian_id=?");
    $stmt->bind_param('ssi', $date,$year,$first_veterinarian);
    $totalbookings=0;
    if($stmt->execute()){
        $result=$stmt->get_result();
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $totalbookings++;
            }

            $stmt -> close();
        }
    }

    return $totalbookings;
    
}

?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css">
    <title>Book Appointment</title>
    <style>
        table{
            table-layout: fixed;
        }
        td{
            width: 33%;
        }
        .today{
            background: yellow;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                    <?php
                        $dateComponents=getdate();
                        if(isset($_GET['month']) && isset($_GET['year'])){
                            $month=$_GET['month'];
                            $year=$_GET['year'];
                        }
                        else{
                            $month=$dateComponents['mon'];
                            $year=$dateComponents['year'];
                        }

                        if(isset($_GET['veterinarian'])){
                            $veterinarian=$_GET['veterinarian'];
                        }
                        else{
                            $veterinarian=0;
                        }
                        
                        echo build_calender($month,$year,$veterinarian);
                    ?>
            </div>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.1.min.js"
  integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ="
  crossorigin="anonymous"></script>
<script>
$('#veterinarian_select').change(function(){
    $('#veterinarian_select_form').submit();
});

$("#veterinarian_select' option[value='<?php echo $veterinarian; ?>']").attr('selected', 'selected');
</script>
</body>
</html>
