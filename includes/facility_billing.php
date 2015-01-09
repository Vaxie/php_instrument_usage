<script>
    function CheckAll() {
        count = document.verifyForm.elements.length;
        for (i = 0; i < count; i++) {
            if (document.verifyForm.elements[i].checked == 1) {
                document.verifyForm.elements[i].checked = 0;
            }
            else {
                document.verifyForm.elements[i].checked = 1;
            }
        }
    }

</script>

<?php
//Declare objects
$rateTypesList = array(Bills::CONTINUOUS_RATE => "Continuous", Bills::MONTHLY_RATE => "Monthly");
$device = new Device($sqlDataBase);
$devicesList = $device->GetDevicesList();
$user = new User($sqlDataBase);
$userList = $user->GetAllUsers();
$bills = new Bills($sqlDataBase);
$session = new Session($sqlDataBase);
$userCfop = new UserCfop($sqlDataBase);

$sessionIdSelected = 0;
$rowSelected = 0;


if (isset($_POST['createSession'])) {
    $startTimeStamp = strtotime($_POST['startDate'] . " " . $_POST['starttime']);
    $start = Date("Y-m-d H:i:s", $startTimeStamp);
    $stop = Date("Y-m-d H:i:s", $startTimeStamp + ($_POST['usage'] * 60 * 60));
    $session->CreateSession($_POST['user_id'], $start, $_POST['stop'], $_POST['status'], $_POST['device_id'], $_POST['description'], $_POST['cfop']);
    $session->SetRate($_POST['rate'] / 60);
    $session->UpdateSession();
    $session->ManualVerify();
}

if (isset($_POST['update_session'])) {
    $session->LoadSession($_POST['edit_session_id']);
    if ($session->GetUserID() == $_POST['user_id']) {
        $session->SetCfopId($_POST['user_cfop_id']);
    } else {
        $session->SetCfopId($userCfop->LoadDefaultCfopl($_POST['user_id']));
    }
    $session->SetUserID($_POST['user_id']);
    $session->SetDeviceID($_POST['device_id']);
    $session->SetRate($_POST['rate'] / 60);
    $session->SetElapsed($_POST['elapsed'] * 60);
    $session->SetStart(date('Y-m-d H:i:s', strtotime($_POST['datetime'])));
    $session->UpdateSession();
    $sessionIdSelected = $_POST['edit_session_id'];
}

if (isset($_GET['session_id'])) {
    if(isset($_GET['rowid']))
    {
        $rowSelected = $_GET['rowid'];
    }
    if(isset($_POST['edit_session_row']))
    {
        $rowSelected = $_POST['edit_session_row'];
    }
    $sessionIdSelected = $_GET['session_id'];
}

if (isset($_POST['monthSelected'])) {
    list($month, $year) = explode(" ", $_POST['monthSelected']);
} else {

    $month = Date("n");
    $year = Date("Y");
}

if ($sessionIdSelected > 0) {
    $session->LoadSession($sessionIdSelected);
    $startDate = $session->GetStart();
    $startDateArr = getdate(strtotime($startDate));
    $month = $startDateArr['mon'];
    $year = $startDateArr['year'];

    echo "<script>
    $(document).ready(function(){
        Element.prototype.documentOffsetTop = function () {
            return this.offsetTop + ( this.offsetParent ? this.offsetParent.documentOffsetTop() : 0 );
        };
        var top = document.getElementById( '" . $sessionIdSelected . "' ).documentOffsetTop() - ( window.innerHeight / 2 );
        window.scrollTo(0,top);

    });
    </script>";
}

?>
<div class="alert alert-info">
    <h4>Facility Billing</h4>

    <p>Usage billing is reported bellow, billing is charged on a monthly
        cycle or per minute usage.</p>
</div>

    <form name="verifyForm" method="post"
          action="./index.php?view=<?php echo $pages->GetPageId('Facility Billing'); ?>">
        <div class="well row-fluid">
            <div class="col-sm-3">
        <select name="monthSelected" class="form-control">
            <?php
            $availableMonths = $bills->GetAvailableBillingMonths();

            foreach ($availableMonths as $id => $availMonth) {
                echo "<option value=\"" . $availMonth['month'] . " " . $availMonth['year'] . "\"";
                if ($availMonth['month'] == $month && $availMonth['year'] == $year) {
                    echo " SELECTED";
                }
                echo ">" . $availMonth['mon_yr'] . "</option>";
            }

            ?>
        </select>
            </div>
            <div class="col-sm-8">
                <input class="btn btn-primary btn-sm" type="submit"
                       name="selectMonth" value="Select Billing Period">
            </div>
            </div>
        <?php
        foreach ($rateTypesList as $rateTypeId => $rateTypeName) {
            $bills->setGroupBy(0);

            if ($rateTypeId == Bills::MONTHLY_RATE) {
                $bills->setGroupBy(Bills::GROUP_DEVICE);
            }

            $monthlyUsage = $bills->GetMonthCharges($year, $month, $rateTypeId);

            echo "<div class=\"panel panel-default\">
                    <div class=\"panel-heading\">
                        <h3>Billed " . $rateTypeName . ":</h3>
                    </div>
                    <div class=\"panel-body\">";

            //Go through each month session
            foreach ($monthlyUsage as $rowId => $monthSession) {
                $rate = $monthSession['rate'];

                //change rate to hours
                $monthlyUsage[$rowId]['rate'] = round($monthSession['rate']*60,2);

                //Change usage to hours
                $monthlyUsage[$rowId]['elapsed'] = round($monthSession['elapsed']/60,2);

                //Minimum usage time
                $monthlyUsage[$rowId]['min_use_time'] = round(($monthSession['min_use_time'] / 60), 2);

                //Show Edit under Options
                $monthlyUsage[$rowId]['options'] = "<a id=" . $monthSession['id'] . " href=\"index.php?view=" . $pages->GetPageId('Facility Billing') . "&session_id=" . $monthSession['id'] . "&rowid=".$rowId."\">Edit</a>";

                //Total String
                $totalString =  "$" . round($bills->CalcTotal($monthSession['elapsed'], $rateTypeId, $rate, $monthSession['min_use_time']), 2);
                $monthlyUsage[$rowId]['total'] =  "$" . round($bills->CalcTotal($monthSession['elapsed'], $rateTypeId, $rate, $monthSession['min_use_time']), 2);

                //Cfop string
                $cfopString = UserCfop::formatCfop($monthSession['cfop']);
                $monthlyUsage[$rowId]['cfop']= $cfopString;

                //If we want to edit this session info then load selected row with input input fields
                if ($session->GetSessionId() == $monthSession['id'])
                {
                    //User options for edit session
                    $userNameString =  "<select name=\"user_id\" class=\"form-control\">";
                    foreach ($userList as $id => $userToSelect) {
                        $userNameString .="<option value=" . $userToSelect["id"];
                        if ($userToSelect["id"] == $monthSession['user_id']) {
                            $userNameString .= " SELECTED";
                        }
                        $userNameString .= ">" . $userToSelect['user_name'] . "</option>";
                    }
                    $userNameString .= "</select>";
                    $monthlyUsage[$rowId]['user_name'] = $userNameString;

                    //Start Time Edit String
                    $startTimeString = "<input type=\"datetime-local\" name=\"datetime\" value=\"".date('Y-m-d\TH:i:s', strtotime($monthSession['start'])) . "\" class=\"form-control\">";
                    $monthlyUsage[$rowId]['start']= $startTimeString;

                    //CFOP options for edit session
                    $userCfopList = $userCfop->ListCfops($monthSession['user_id']);
                    $cfopString = "<select name=\"user_cfop_id\" class=\"form-control\">";
                    foreach ($userCfopList as $userCfopInfo) {
                        $cfopString .= "<option value=" . $userCfopInfo['id'];
                        if ($monthSession['cfop_id'] == $userCfopInfo['id']) {
                            $cfopString .= " SELECTED";
                        }
                        $cfopString .= ">" . UserCfop::formatCfop($userCfopInfo['cfop']) . "</option>";
                    }
                    $cfopString .= "</select>";
                    $monthlyUsage[$rowId]['cfop']= $cfopString;

                    //Device selection edit string
                    $deviceString = "<select name=\"device_id\" class=\"form-control\">";
                    foreach ($devicesList as $id => $deviceToSelect) {
                        $deviceString .= "<option value=" . $deviceToSelect["id"];
                        if ($deviceToSelect["id"] == $monthSession['device_id']) {
                            $deviceString .= " SELECTED";
                        }
                        $deviceString .= ">" . $deviceToSelect["device_name"] . "</option>";
                    }
                    $deviceString .= "</select>";
                    $monthlyUsage[$rowId]['full_device_name']= $deviceString;

                    //Elapsed time edit string
                    $elapsedTimeString = "<input type=\"text\" name=\"elapsed\" value=\"" . round(($monthSession['elapsed'] / 60), 2) . "\" class=\"form-control\">";
                    $monthlyUsage[$rowId]['elapsed']=$elapsedTimeString;

                    //Min use time edit string
                    $minUseTimeString = "<input type=\"text\" name=\"min_use_time\" value=\"".round(($monthSession['min_use_time'] / 60), 2)."\" class=\"form-control\">";
                    $monthlyUsage[$rowId]['min_use_time'] = $minUseTimeString;

                    //Rate String
                    $rateString = "<input type=\"text\" name=\"rate\" value=\"" . round(($rate * 60), 2) . "\" class=\"form-control\">";
                    $monthlyUsage[$rowId]['rate']=$rateString;

                    //Description Edit String
                    $descriptionString = "<textarea name=\"description\" class=\"form-control\">" . $monthSession['description'] . "</textarea>";
                    $monthlyUsage[$rowId]['description']=$descriptionString;

                    //Options
                    $optionsString =  "<a id=" . $monthSession['id'] . "></a>
                                        <input type=\"submit\" value=\"Update\" name=\"update_session\" class=\"btn btn-primary btn-sm\">
                                        <input type=\"hidden\" name=\"edit_session_id\" value=" . $monthSession['id'] . ">
                                        <input type=\"hidden\" name=\"edit_session_row\" value=".$rowId.">";
                    $monthlyUsage[$rowId]['options'] = $optionsString;
                }

            }

            echo "<div class=\"row-fluid\">";
            echo VisualizeData::ListSessionsTable($monthlyUsage,
                array('id','Name','Date','CFOP','Instrument','Hours','Min. Hours','Rate per Hours','Rate Type','Total','options'),
                array('id','user_name','start','cfop','full_device_name','elapsed','min_use_time','rate','rate_name','total','options'),$rateTypeName."_table",$rowSelected);

            echo "</div></div></div>";
        }
        ?>
        </tbody>

    </form>
