<?
/***
 * Orignial work: https://github.com/anderssonjohan/Nettest
 * */
$cfg=parse_ini_file("speed.ini",true);
foreach ($cfg as $key=>$val) {
	$cdn = ($val['cdn']==true) ? "true":"false";
	$locationcfg[]="{'location':'".$key."','url':'".$val['url']."','cdn':".$cdn."}";
}
?>
<html>
<head>
<title>Spuul Speedtest
</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
<script type="text/javascript">
var start_time = 0;
function Timer()
{
	this.startTime = 0;
	this.endTime = 0;

	Timer.prototype.Reset = function()
	{
		this.startTime = new Date().getTime();
		this.endTime = 0;
	}

	Timer.prototype.Stop = function()
	{
		this.endTime = new Date().getTime();
		return this.endTime - this.startTime;
	}

	Timer.prototype.Value = function()
	{
		var end = this.endTime;
		if( end == 0 && this.startTime != 0 )
			end = new Date().getTime();
		return end - this.startTime;
	}

	this.Reset();
}
Number.prototype.timeString = function()
{
	if( this >= 1000 )
		return roundNumber( this / 1024, 2 ).toString() + " s";
	return this.toString() + " ms";
}

Number.prototype.speedString = function()
{
	if( this >= 1024 )
		return roundNumber( this / 1024, 1 ).toString() + " Mbit/s";
	return this.toString() + " kbit/s";
}

function roundNumber(num, dec)
{
  var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
  return result;
}

function WebClientRequest( method, url )
{
	this.timer = new Timer;
	this.url = url;
	this.data = "";
	this.method = method;

	this.GetResponse = function( readData )
	{
		var response = new WebClientResponse( this, readData );
		this.timer.Reset();
		$.ajax( {
			url: this.url,
			cache: false,
			async: false,
			processData: false,
			data: this.data,
			type: this.method,
			dataType: "text",
			contentType: "text/plain; charset=x-user-defined",
			complete: response.InternalRequestCompleted(),
			error: function( e )
			{
				if( e.status == 200 )
					return; // We do get here in IE7 on Vista, even though the request performed well..
				if( e.status == 405 )
					alert( "Your web server does not allow HTTP POST to this HTML file.\nPlease review your configuration." );
			}
		} );

		return response;
	};
}

function WebClientResponse( request, readData )
{
	this.request = request;
	this.readData = readData;
	this.data = "";
	this.status = "";
	this.statusText = "";
	this.contentLength = 0;
	this.bytesTransferred = 0;
	this.GetTime = function() { return this.request.timer.Value(); }
	this.GetSpeed = function() { return roundNumber( ( this.bytesTransferred * 8 / 1024 ) / ( this.GetTime() / 1000 ), 0 ); }

	this.InternalRequestCompleted = function()
	{
		var response = this;
		return function( xhr )
		{
			response.status = xhr.status;
			response.statusText = xhr.statusText;
			var contentLengthHeader = xhr.getResponseHeader( "Content-Length" );

			if( null != contentLengthHeader && contentLengthHeader.length > 0 )
				response.contentLength = parseInt( contentLengthHeader );
			else if( null != xhr.responseText )
				response.contentLength = xhr.responseText.length;
			else
				response.contentLength = 0;

			if( response.request.method == "POST" )
				response.contentLength = request.data.length;
			if( response.request.method != "HEAD" )
				response.bytesTransferred = response.contentLength;
			if( response.readData )
				response.data = xhr.responseText;
				response.request.timer.Stop();
		}
    };
}

function WebClient()
{
	WebClient.prototype.Download = function( url, readData )
	{
		return new WebClientRequest( "GET", url ).GetResponse( readData );
	};

	WebClient.prototype.Upload = function( url, data )
	{
		if( url.indexOf( "?" ) == -1 )
			qs = '?sid=' + Math.random();
		else
			qs = '&sid=' + Math.random();

		var request = new WebClientRequest( "POST", url + qs );
		request.data = data;
		return request.GetResponse();
	};

	WebClient.prototype.Ping = function( url )
	{
		return new WebClientRequest( "HEAD", url ).GetResponse();
	};
}

Test.Download = "DOWNLOAD";
Test.testFiles=[<?= join(",",$locationcfg)?>];
function Test()
{
	this.timer = new Timer;
	this.webClient = new WebClient;
	this.progressCallback = function() {};
	this.completionCallback = function() {};
	this.progressValue = 0;
	this.maxProgressValue = 0;
	this.path = "";
	this.testId = null;

	this.last = null;
	this.ping = null;

	Test.prototype.Start = function()
	{
		var test = this;
		setTimeout( function()
		{
			test.timer.Reset();
			test.Run( function()
			{
				test.progressValue++;
				if( null != test.progressCallback )
				{
					test.progressCallback();
				}
			},
			function()
			{
				test.timer.Stop();
				if( null != test.completionCallback )
					test.completionCallback();
			} );
		}, 10 );
	}

	Test.prototype.Run = function( UpdateProgress, completed ) {}

	Test.prototype.MakeRequest = function()
	{
		if( this.path == null || this.path.length == 0 )
			return;
		if( this.method == Test.Upload )
		{
			if( this.payload == null )
			{
				var dl = this.webClient.Ping( this.path );
				this.payload = this.GenerateData( dl.contentLength );
			}

			this.last = this.webClient.Upload( location.search, this.payload );
		}
		else if( this.method == Test.Download )
		{
			this.last = this.webClient.Download( this.path );
		}
        	this.ping = this.webClient.Ping( this.path );
	}

	Test.prototype.GenerateData = function( length )
	{
		var data = "";
		while( data.length < length )
		{
			data = data + Math.random();
		}
		return data;
	}


	Test.prototype.OnProgress = function( progressCallback )
    {
        this.progressCallback = progressCallback;
		return this;
	}

	Test.prototype.OnComplete = function( completionCallback )
	{
		this.completionCallback = completionCallback;
		return this;
	}

	Test.prototype.Path = function( path )
	{
		this.path = path;
		return this;
	}

	Test.prototype.TestId = function(number)
	{
		this.testId = number;
		return this;
	}

	Test.prototype.Method = function( method )
	{
		this.method = method;
		return this;
	}
}

AverageTest.prototype = new Test;
function AverageTest()
{
	this.iterations = 0;
	this.iteration = 0;
	this.totalBytes = 0;
	this.totalSpeed = 0;
	this.speedDetails = [];
	this.totalTime = 0;
	this.totalPing = 0;
	
	this.iterationCallback = function() {};
	
	Test.apply( this, arguments );
	
	AverageTest.prototype.Run = function( incrementProgress, completed )
	{
		if (this.iteration==0 && Test.testFiles[this.testId].cdn) {
			this.MakeRequest();
		}
		this.maxProgressValue = this.iterations;
		if( !this.IsCompleted() )
		{
			this.Iterate();
			if( this.last == null )
				return;
			this.iteration++;
			incrementProgress();
			var test = this;
			setTimeout( function() { test.Run( incrementProgress, completed ); }, 1 );
		}
		else
			completed();
	}
	
	AverageTest.prototype.IsCompleted = function()
	{
		return this.iterations == this.iteration;
	}
	
	AverageTest.prototype.GetAverageTime = function()
	{
		return roundNumber( this.totalTime / this.iteration, 2 );
	}
	
	AverageTest.prototype.GetAveragePing = function()
	{
		return roundNumber( this.totalPing / this.iteration, 2 );
	}
	
	AverageTest.prototype.GetAverageSpeed = function()
	{
		return roundNumber( this.totalSpeed / this.iteration, 1 );
	}
	
	AverageTest.prototype.GetTotalTime = function()
	{
		return this.totalTime;
	}
	
	AverageTest.prototype.Iterate = function()
	{
		this.MakeRequest();
		if( this.last != null )
		{
			this.totalBytes += this.last.contentLength;
			this.totalSpeed += this.last.GetSpeed();
			this.speedDetails.push(this.last.GetSpeed().speedString());
			this.totalTime += this.last.GetTime();
			this.totalPing += this.ping.GetTime();
		}
	}
	
	AverageTest.prototype.Iterations = function( number )
	{
		this.iterations = number;
		return this;
	}
	
	AverageTest.prototype.AfterIteration = function( iterationCallback )
	{
		this.iterationCallback = iterationCallback;
		return this;
	}
}

function StartTest(testId,count)
{
	UpdateProgress(testId,0);
	$( "#progressbar_"+testId ).show( "normal", function() { TestStarted( testId,count ); } );
}

function UpdateProgress( testId, value )
{
	$( "#progressbar_"+testId ).children(".progressText").html("Running...");
	$( "#progressbar_"+testId ).children(".progressbar").css( "width", value + "%" );
}

function TestStarted( testId, count )
{
	var upload = null;
	var download = new AverageTest()
		.TestId( testId )
		.Iterations( count )
		.Path( Test.testFiles[testId].url )
		.Method( Test.Download )
		.OnProgress( function()
		{
			var progressPercentage = this.progressValue / (this.maxProgressValue / 100 );
			UpdateProgress( testId, progressPercentage );
			//AppendDetailsRow( this );
		} );
    
	download.OnComplete( function() { EndTest( download ); } ).Start();
}

function storeResult() {
	$.post('store.php',{'json':JSON.stringify(result),'email':$('#email').val()});
}
function EndTest(res)
{
	testId=res.testId;
	avgPing=res.GetAveragePing();
	avgSpeed=res.GetAverageSpeed();
	$('#ping_'+testId).html(avgPing.timeString());
	$('#speed_'+testId).html(avgSpeed.speedString()+" ( "+res.speedDetails.join(", ")+")");
	result[testId]={'location':Test.testFiles[testId].location,'ping':avgPing.timeString(),'speed':avgSpeed.speedString(),'speedDetails':res.speedDetails.join(', ')};
	nextId=testId+1;
	if (nextId<Test.testFiles.length) {
		StartTest(nextId,5);
	}
	else {
		storeResult();
	}
}

var result=[];
$( function() {
	$('#email').keypress(function(e) {
		$('#email').removeClass('error');
	});
	$('#startme').click( function(e)
	{
		if ($('#email').val()=="") {
			$('#email').addClass('error');
			return false;
		}
		count = 5;
		StartTest(0,count);
	});
});
</script>
<style type="text/css">
#email {
	border:1px solid black;
	background:white;
}
#email.error {
	border:2px solid red;
	background: #FFD0D0;
}
.progressbarContainer {
	position:relative;
	width:400px;
	height:15px;
	border:1px solid black;
}
.progressbar {
	position:absolute;
	width:0%;
	height:100%;
	background-color:red;
	z-index:1;
}
.progressText {
	position:absolute;
	font-size:80%;
	width:100%;
	height:100%;
	z-index:2;
	padding-left:2px;
}
</style>
<body>
<h1>Spuul Speed Test</h1>
<form action="<?= $_SERVER['PHP_SELF']?>" method="GET">
Your E-Mail: <input type="text" name="email" id="email" />
<input type="button" id="startme" value="Start">
</form>
<table border="1" cellpadding="2" cellspacing="2">
<tr>
<th>Location</th>
<th>Ping</th>
<th>Speed</th>
</tr>
<?
$n=0;
foreach ($cfg as $key=>$val) {
	print "<tr>\n";
	print "<td>".$key."</td>\n";
	print "<td><div id='ping_".$n."'/></td>\n";
	print "<td><div id='speed_".$n."'><div id='progressbar_".$n."' class='progressbarContainer'><div class='progressbar'></div><div class='progressText'>Waiting...</div></div></td>\n";
	print "</tr>\n";
	$n++;
}
?>
</table>
</body>
</html>
