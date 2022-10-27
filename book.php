<?php
$mysqli=new mysqli('localhost','root','','bookingcalendar');
if(isset($_GET['date'])){
    $date = $_GET['date'];
    $veterinarian_id = $_GET['veterinarian_id'];
    $stmt=$mysqli->prepare("SELECT * FROM bookings WHERE date=? AND veterinarian_id=?");
    $stmt->bind_param('si', $date,$veterinarian_id);
    $bookings = array();
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows>0){
            while($row = $result->fetch_assoc()){
                $bookings[]=$row['timeslot'];
            }
            $stmt->close();
        }
    }
}

if(isset($_POST['submit'])){
    $name=$_POST['name'];
    $email=$_POST['email'];
    $timeslot=$_POST['timeslot'];
    $phone=$_POST['phone'];
    $reason=$_POST['reason'];
    $stmt=$mysqli->prepare("SELECT * FROM bookings WHERE date=? and timeslot=? AND veterinarian_id=?");
    $stmt->bind_param('ssi', $date,$timeslot,$veterinarian_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows>0){
            $msg="<div class='alert alert-danger'>Already Booked!</div>";
        }
        else{
            $stmt=$mysqli->prepare("INSERT INTO bookings (name,email,date,timeslot,phone,reason,veterinarian_id) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssi', $name,$email,$date,$timeslot,$phone,$reason,$veterinarian_id);
            $stmt->execute();
            $msg="<div class='alert alert-success'>Booking Successful!</div>";
            $bookings[]=$timeslot;
            $stmt->close();
            $mysqli->close();
        }
    }   
}
$duration=60;
$cleanup = 0;
$start="09:00";
$end="17:00";

function timeslots($duration,$cleanup,$start,$end){
    $start = new DateTime($start);
    $end = new DateTime($end);
    $interval=new DateInterval("PT".$duration."M");
    $cleanupInterval = new DateInterval("PT".$cleanup."M");
    $slots=array();

    for($intStart=$start;$intStart<$end;$intStart -> add($interval) -> add($cleanupInterval)){
        $endPeriod= clone $intStart;
        $endPeriod->add($interval);
        if($endPeriod>$end){
            break;
        }

        $slots[]=$intStart->format("H:iA")."-".$endPeriod->format("H:iA");

    }

    return $slots;

}
?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js" integrity="sha384-7VPbUDkoPSGFnVtYi0QogXtr74QeVeeIs99Qfg5YCF+TidwNdjvaKZX19NZ/e6oz" crossorigin="anonymous"></script>
    <title>Book Appointment</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    
</head>
<body>
    
    <div class="container">
        <h1 class="text-center">Book for Date:<?php echo date('d/m/Y',strtotime($date)); ?></h1><hr>
        <?php echo isset($msg)?$msg:"";?>
        <div class="row">
            <?php $timeslots = timeslots($duration,$cleanup,$start,$end); 
                foreach($timeslots as $ts){
                    
            ?>

            <div class="col-md-2">
                <div class="form-group">
                    <?php if(in_array($ts,$bookings)){?>
                        <button class="btn btn-danger"><?php echo $ts; ?></button>
                    <?php }else{ ?>
                        <button class="btn btn-success book" data-timeslot="<?php echo $ts; ?>"><?php echo $ts; ?></button>
                    <?php }?>
                </div>
            </div>

            <?php } ?>
            
        </div>
    </div>
    <div id="myModal" class="modal fade" role="dialog">
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Booking: <span id="slot"></span></h4>
                </div>
                <div class='modal-body'>
                    <div class='row'>
                        <div class="col-md-12">
                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="">Timeslot</label>
                                    <input type="text" name="timeslot" id="timeslot" class="form-control" readonly required>
                                </div>
                                <div class="form-group">
                                    <label for="">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="">Phone</label>
                                    <input type="text" class="form-control" name="phone" required>
                                </div>
                                <div class="form-group">
                                    <label for="">Reason for visit</label>
                                    <input type="text" class="form-control" name="reason" required>
                                </div>
                                
                                <button class="btn btn-primary" type="submit" name="submit">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type="button" class="close" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(".book").click(function(){
            var timeslot= $(this).attr('data-timeslot');
            $("#slot").html(timeslot);
            $("#timeslot").val(timeslot);
            $("#myModal").modal("show");
        })
    </script>
</body>
</html>