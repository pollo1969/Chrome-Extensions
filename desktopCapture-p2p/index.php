<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<title>WebRTC Desktop Viewer</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">

<meta name="description" content="This WebRTC Experiment page shows privately shared screens, desktops, and parts of the screens." />
<meta name="keywords" content="WebRTC,Desktop-Sharing,Screen-Sharing,RTCWeb,WebRTC-Experiment,WebRTC-Demo" />

<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<link rel="author" type="text/html" href="https://plus.google.com/+MuazKhan">
<meta name="author" content="Muaz Khan">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

<script type="text/javascript"><?php readfile(getcwd()."/socket.io.js"); ?></script>
<script type="text/javascript"><?php readfile(getcwd()."/adapter.js"); ?></script>
<script type="text/javascript"><?php readfile(getcwd()."/RTCMultiConnection.min.js"); ?></script>
<script type="text/javascript"><?php readfile(getcwd()."/CodecsHandler.js"); ?></script>
<script type="text/javascript"><?php readfile(getcwd()."/IceServersHandler.js"); ?></script>
<script type="text/javascript"><?php readfile(getcwd()."/getStats.js"); ?></script>

<style>
body,
html {
    background: black;
    text-align: center;
    color: white;
    overflow: hidden;
}
.local-media,
.remote-media {
    max-width: 100%;
    max-height: 70%;
}
.local-media-small,
.remote-media-small {
    width: 20%;
    position: fixed;
    bottom: 0;
    left: 0;
}
button {
    display: inline-block;
    outline: 0;
    color: white;
    background: #4472b9;
    white-space: nowrap;
    border: 5px solid #4472b9 !important;
    font-family: 'Gotham Rounded A', 'Gotham Rounded B', sans-serif;
    font-weight: 500;
    font-style: normal;
    padding: 9px 16px !important;
    line-height: 1.4;
    position: relative;
    border-radius: 10px;
    -webkit-box-shadow: 5px 5px 0 0 rgba(0, 0, 0, 0.15);
    box-shadow: 5px 5px 0 0 rgba(0, 0, 0, 0.15);
    -webkit-transition: 0.1s;
    transition: 0.1s;
}
button:hover,
button:active,
button:focus {
    background: #04C;
}
button[disabled] {
    background: transparent;
    border-color: rgb(83, 81, 81);
    color: rgb(139, 133, 133);
}
#container {
    -webkit-perspective: 1000;
    background-color: #000000;
    height: 100%;
    margin: 0px auto;
    position: absolute;
    width: 100%;
}
#card {
    -webkit-transform-style: preserve-3d;
    -webkit-transition-duration: 2s;
    -webkit-transition-property: rotation;
}
#local {
    -webkit-backface-visibility: hidden;
    -webkit-transform: scale(-1, 1);
    position: absolute;
    width: 100%;
}
#remote {
    -webkit-backface-visibility: hidden;
    -webkit-transform: rotateY(180deg);
    position: absolute;
    width: 100%;
}
#mini {
    /* -webkit-transform: scale(-1, 1); */

    bottom: 0;
    height: 30%;
    opacity: 1.0;
    position: absolute;
    right: 4px;
    width: 30%;
}
#remoteVideo {
    -webkit-transition-duration: 2s;
    -webkit-transition-property: opacity;
    height: 100%;
    opacity: 0;
    width: 100%;
}
#info-bar {
    background-color: #15DBFF;
    bottom: 55%;
    color: rgb(255, 255, 255);
    font-size: 25px;
    font-weight: bold;
    height: 38px;
    line-height: 38px;
    position: absolute;
    text-align: center;
    width: 100%;
    text-shadow: 1px 1px rgb(14, 105, 137);
    border: 2px solid rgb(47, 102, 118);
    box-shadow: 0 0 6px white;
}
#stats-bar {
    background-color: rgba(255, 255, 255, 0.92);
    top: 20px;
    left: 20px;
    color: black;
    font-size: 17px;
    line-height: 1.5em;
    position: absolute;
    border: 2px solid rgba(0, 0, 0, 0.82);
    border-radius: 7px;
    font-family: Arial;
    
    text-align: left;
    display: none;
}

#stats-bar-html {
    padding: 5px 10px;
}

#hide-stats-bar {
    float: right;
    cursor: pointer;
    color: red;
    font-size: 20px;
    font-weight: bold;
    margin-right: 8px;
}

#hide-stats-bar:hover, #hide-stats-bar:active {
    color: #6c1414;
}
</style>

<div id="container" ondblclick="enterFullScreen()">
    <div id="card">
        <div id="remote">
            <video id="remoteVideo" autoplay playsinline></video>
        </div>
    </div>

    <div id="info-bar"></div>
    <div id="stats-bar">
        <div id="hide-stats-bar">x</div>
        <div id="stats-bar-html"></div>
    </div>
</div>

<script>
(function() {
    var params = {},
        r = /([^&=]+)=?([^&]*)/g;

    function d(s) {
        return decodeURIComponent(s.replace(/\+/g, ' '));
    }

    var match, search = window.location.search;
    while (match = r.exec(search.substring(1)))
        params[d(match[1])] = d(match[2]);

    window.params = params;
})();

// http://www.rtcmulticonnection.org/docs/constructor/
var connection = new RTCMultiConnection(params.s);
// connection.socketURL = 'https://rtcmulticonnection.herokuapp.com:443/';
connection.socketURL = 'https://webrtcweb.com:9001/';

connection.enableLogs = true;
connection.session = {
    audio: true,
    video: true,
    oneway: true
};

// www.rtcmulticonnection.org/docs/sdpConstraints/
connection.sdpConstraints.mandatory = {
    OfferToReceiveAudio: true,
    OfferToReceiveVideo: true
};

connection.getExternalIceServers = false;
connection.iceServers = IceServersHandler.getIceServers();

function setBandwidth(sdp) {
    sdp = sdp.replace(/b=AS([^\r\n]+\r\n)/g, '');
    sdp = sdp.replace(/a=mid:video\r\n/g, 'a=mid:video\r\nb=AS:10000\r\n');
    return sdp;
}

connection.processSdp = function(sdp) {
    var bandwidth = params.bandwidth;
    var codecs = params.codecs;
    
    if (bandwidth) {
        try {
            bandwidth = parseInt(bandwidth);
        } catch (e) {
            bandwidth = null;
        }

        if (bandwidth && bandwidth != NaN && bandwidth != 'NaN' && typeof bandwidth == 'number') {
            sdp = setBandwidth(sdp, bandwidth);
            sdp = BandwidthHandler.setVideoBitrates(sdp, {
                min: bandwidth,
                max: bandwidth
            });
        }
    }

    if (!!codecs && codecs !== 'default') {
        sdp = CodecsHandler.preferCodec(sdp, codecs);
    }
    return sdp;
};

connection.optionalArgument = {
    optional: [],
    mandatory: {}
};
</script>

<script>
// DOM objects
var remoteVideo = document.getElementById('remoteVideo');
var card = document.getElementById('card');
var containerDiv;

if (navigator.mozGetUserMedia) {
    attachMediaStream = function(element, stream) {
        console.log("Attaching media stream");
        element.mozSrcObject = stream;
        element.play();
    };
    reattachMediaStream = function(to, from) {
        console.log("Reattaching media stream");
        to.mozSrcObject = from.mozSrcObject;
        to.play();
    };
} else if (navigator.webkitGetUserMedia) {
    attachMediaStream = function(element, stream) {
        if (typeof element.srcObject !== 'undefined') {
            element.srcObject = stream;
        } else if (typeof element.mozSrcObject !== 'undefined') {
            element.mozSrcObject = stream;
        } else if (typeof element.src !== 'undefined') {
            element.src = URL.createObjectURL(stream);
        } else {
            console.log('Error attaching stream to element.');
        }
    };
    reattachMediaStream = function(to, from) {
        to.src = from.src;
    };
} else {
    console.log("Browser does not appear to be WebRTC-capable");
}
// onstream event; fired both for local and remote videos

var infoBar = document.getElementById('info-bar');

connection.onstatechange = function(state) {
    infoBar.innerHTML = state.name + ': ' + state.reason;

    if(state.name == 'request-rejected' && params.p) {
        infoBar.innerHTML = 'Password (' + params.p + ') did not match with broadcaster, that is why your participation request has been rejected.<br>Please contact him and ask for valid password.';
    }

    if(state.name === 'room-not-available') {
        infoBar.innerHTML = 'Screen share session is closed or paused. You will join automatically when share session is resumed.';
    }
};

connection.onstreamid = function(event) {
    infoBar.innerHTML = 'Remote peer is about to send his screen.';
};

connection.onstream = function(e) {
    if (e.type == 'remote') {
        connection.remoteStream = e.stream;

        infoBar.style.display = 'none';
        remoteStream = e.stream;
        attachMediaStream(remoteVideo, e.stream);
        waitForRemoteVideo();
        remoteVideo.setAttribute('data-id', e.userid);

        connection.socket.emit(connection.socketCustomEvent, {
            receivedYourScreen: true
        });
    }
};
// if user left
connection.onleave = function(e) {
    if(e.userid !== params.s) return;

    transitionToWaiting();
    connection.onSessionClosed();

    location.reload();
};

connection.onSessionClosed = function() {
    infoBar.innerHTML = 'Screen sharing has been closed.';
    infoBar.style.display = 'block';
    statsBar.style.display = 'none';
    connection.close();
    connection.closeSocket();
    connection.userid = connection.token();

    remoteVideo.pause();
    remoteVideo.src = 'https://cdn.webrtc-experiment.com/images/muted.png';

    setTimeout(checkPresence, 2000);
};

connection.ondisconnected = connection.onSessionClosed;
connection.onstreamended = connection.onSessionClosed;

function waitForRemoteVideo() {
    // Call the getVideoTracks method via adapter.js.
    var videoTracks = remoteStream.getVideoTracks();
    if (videoTracks.length === 0 || remoteVideo.currentTime > 0) {
        transitionToActive();
    } else {
        setTimeout(waitForRemoteVideo, 100);
    }
}

function transitionToActive() {
    remoteVideo.style.opacity = 1;
    card.style.webkitTransform = 'rotateY(180deg)';
    window.onresize();
}

function transitionToWaiting() {
        card.style.webkitTransform = 'rotateY(0deg)';
        remoteVideo.style.opacity = 0;
    }
    // Set the video displaying in the center of window.
window.onresize = function() {
    var aspectRatio;
    if (remoteVideo.style.opacity === '1') {
        aspectRatio = remoteVideo.videoWidth / remoteVideo.videoHeight;
    } else {
        return;
    }
    var innerHeight = this.innerHeight;
    var innerWidth = this.innerWidth;
    var videoWidth = innerWidth < aspectRatio * window.innerHeight ?
        innerWidth : aspectRatio * window.innerHeight;
    var videoHeight = innerHeight < window.innerWidth / aspectRatio ?
        innerHeight : window.innerWidth / aspectRatio;
    containerDiv = document.getElementById('container');
    containerDiv.style.width = videoWidth + 'px';
    containerDiv.style.height = videoHeight + 'px';
    containerDiv.style.left = (innerWidth - videoWidth) / 2 + 'px';
    containerDiv.style.top = (innerHeight - videoHeight) / 2 + 'px';
};

function enterFullScreen() {
    container.webkitRequestFullScreen();
}
</script>

<script>
connection.onJoinWithPassword = function(remoteUserId) {
    if(!params.p) {
        params.p = prompt(remoteUserId + ' is password protected. Please enter the pasword:');
    }

    connection.password = params.p;
    connection.join(remoteUserId);
};

connection.onInvalidPassword = function(remoteUserId, oldPassword) {
    var password = prompt(remoteUserId + ' is password protected. Your entered wrong password (' + oldPassword + '). Please enter valid pasword:');
    connection.password = password;
    connection.join(remoteUserId);
};

connection.onPasswordMaxTriesOver = function(remoteUserId) {
    alert(remoteUserId + ' is password protected. Your max password tries exceeded the limit.');
};

connection.socketCustomEvent = params.s;

function checkPresence() {
    infoBar.innerHTML = 'Checking room: ' + params.s;

    connection.checkPresence(params.s, function(isRoomExist) {
        if (isRoomExist === false) {
            infoBar.innerHTML = 'Room does not exist: ' + params.s;

            setTimeout(function() {
                infoBar.innerHTML = 'Checking room: ' + params.s;
                setTimeout(checkPresence, 1000);
            }, 4000);
            return;
        }

        infoBar.innerHTML = 'Joining room: ' + params.s;

        connection.password = null;
        if (params.p) {
            connection.password = params.p;
        }

        connection.join(params.s);
    });
}

if(params.s) {
    checkPresence();
}

var dontDuplicate = {};
connection.onPeerStateChanged = function(event) {
    if(!connection.getRemoteStreams(params.s).length) {
        if(event.signalingState === 'have-remote-offer') {
            infoBar.innerHTML = 'Received WebRTC offer from: ' + params.s;
        }

        else if(event.iceGatheringState === 'complete' && event.iceConnectionState === 'connected') {
            infoBar.innerHTML = 'WebRTC handshake is completed. Waiting for remote video from: ' + params.s;
        }
    }

    if(event.iceConnectionState === 'connected' && event.signalingState === 'stable') {
        if(dontDuplicate[event.userid]) return;
        dontDuplicate[event.userid] = true;

        if(DetectRTC.browser.name === 'Safari' || DetectRTC.browser.name === 'Edge') {
            // todo: getStats for safari/edge?
            return;
        }

        var peer = connection.peers[event.userid].peer;

        if(DetectRTC.browser.name === 'Firefox') {
            getStats(peer, (connection.remoteStream || peer.getRemoteStreams()[0]).getTracks()[0], function(stats) {
                onGettingWebRCStats(stats, event.userid);
            }, 1000);
            return;
        }

        getStats(peer, function(stats) {
            onGettingWebRCStats(stats, event.userid);
        }, 1000);

        statsBar.style.display = 'block';
    }
};

var statsBar = document.getElementById('stats-bar');
var statsBarHTML = document.getElementById('stats-bar-html');
var NO_MORE = false;

document.getElementById('hide-stats-bar').onclick = function() {
    statsBar.style.display = 'none';
    NO_MORE = true;
};

function onGettingWebRCStats(stats, userid) {
    if(!connection.peers[userid] || NO_MORE) {
        stats.nomore();
        return;
    }

    var html = 'Codecs: ' + stats.audio.recv.codecs.concat(stats.video.recv.codecs).join(', ');
    html += '<br>';
    html += 'Resolutions: ' + stats.resolutions.recv.width + 'x' + stats.resolutions.recv.height;
    html += '<br>';
    html += 'Data: ' + bytesToSize(stats.audio.bytesReceived + stats.video.bytesReceived);
    // html += '<br>';
    // html += 'Speed: ' + bytesToSize(stats.bandwidth.speed || 0);
    statsBarHTML.innerHTML = html;
}

function bytesToSize(bytes) {
    var k = 1000;
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes === 0) {
        return '0 Bytes';
    }
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(k)), 10);
    return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
}

window.addEventListener('offline', function() {
    infoBar.innerHTML = 'You seems offLine.';
}, false);

window.addEventListener('online', function() {
    infoBar.innerHTML = 'You seems onLine. Reloading the page..';
    location.reload();
}, false);
</script>
