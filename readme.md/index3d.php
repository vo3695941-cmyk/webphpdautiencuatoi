<?php
$prompt = trim($_POST["prompt"] ?? "ngôi nhà, cây và mặt trời");

if (strlen($prompt) > 300) {
    $prompt = substr($prompt, 0, 300);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CODEBRAIN IMAGE 2.1</title>

<style>
*{box-sizing:border-box}
body{
    margin:0;
    padding:15px;
    background:#101522;
    color:#edf2ff;
    font-family:Arial,sans-serif
}
.container{max-width:950px;margin:auto}
h1{font-size:24px;color:#65d8ff}
.panel{
    background:#1b2537;
    padding:15px;
    border-radius:14px;
    margin-bottom:14px
}
input{
    width:100%;
    padding:13px;
    border:0;
    border-radius:9px;
    background:#0d1422;
    color:white;
    font-size:16px
}
button{
    border:0;
    padding:12px 15px;
    border-radius:9px;
    background:#1688d4;
    color:white;
    font-weight:bold;
    margin:5px 3px;
    cursor:pointer
}
button.secondary{background:#34445e}
canvas{
    width:100%;
    height:430px;
    display:block;
    background:#9bc9e8;
    border-radius:12px;
    touch-action:none
}
.small{font-size:13px;color:#b8c7dc;line-height:1.6}
.status{color:#71e4a4;font-size:13px}
</style>
</head>

<body>
<div class="container">

<h1>CODEBRAIN IMAGE 2.1</h1>
<p class="small">MULTI-OBJECT 3D ENGINE • WEBGL • OFFLINE</p>

<div class="panel">
<form method="POST">
    <label>Nhập mô tả hình ảnh:</label>
    <br><br>

    <input
        name="prompt"
        maxlength="300"
        value="<?= htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8') ?>"
    >

    <br><br>
    <button type="submit">Tạo mô hình 3D</button>
</form>

<p class="small">
Ví dụ:<br>
2 ngôi nhà và 3 cây<br>
4 robot màu đỏ<br>
5 người que đang chạy<br>
2 nhà, 3 cây, 4 robot và mặt trời<br>
3 đám mây và 5 cây
</p>
</div>

<div class="panel">
    <canvas id="scene"></canvas>

    <p class="status" id="status">Đang khởi tạo WebGL...</p>

    <button onclick="resetCamera()">Reset camera</button>
    <button class="secondary" onclick="zoom(0.8)">Phóng to +</button>
    <button class="secondary" onclick="zoom(1.25)">Thu nhỏ -</button>
    <button class="secondary" onclick="saveImage()">Tải PNG</button>

    <p class="small">
    Kéo màn hình để xoay mô hình 3D.
    Dùng thao tác cuộn hoặc hai nút phóng to để thay đổi khoảng cách.
    </p>
</div>

</div>

<script>
"use strict";

/* =====================================
   PROMPT
===================================== */

const PROMPT = <?= json_encode(
    $prompt,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

const canvas = document.getElementById("scene");
const statusText = document.getElementById("status");

const gl = canvas.getContext("webgl", {
    antialias:true,
    preserveDrawingBuffer:true
});

if(!gl){
    statusText.textContent = "Trình duyệt không hỗ trợ WebGL.";
    throw new Error("WebGL unavailable");
}

/* =====================================
   SHADERS
===================================== */

const vertexShader = `
attribute vec3 position;
attribute vec3 color;

uniform mat4 mvp;

varying vec3 vColor;

void main(){
    gl_Position = mvp * vec4(position,1.0);
    vColor = color;
}
`;

const fragmentShader = `
precision mediump float;

varying vec3 vColor;

void main(){
    gl_FragColor = vec4(vColor,1.0);
}
`;

function compile(type, source){
    const shader = gl.createShader(type);

    gl.shaderSource(shader,source);
    gl.compileShader(shader);

    if(!gl.getShaderParameter(shader,gl.COMPILE_STATUS)){
        throw new Error(gl.getShaderInfoLog(shader));
    }

    return shader;
}

const program = gl.createProgram();

gl.attachShader(
    program,
    compile(gl.VERTEX_SHADER,vertexShader)
);

gl.attachShader(
    program,
    compile(gl.FRAGMENT_SHADER,fragmentShader)
);

gl.linkProgram(program);

if(!gl.getProgramParameter(program,gl.LINK_STATUS)){
    throw new Error(gl.getProgramInfoLog(program));
}

gl.useProgram(program);

const positionLocation =
    gl.getAttribLocation(program,"position");

const colorLocation =
    gl.getAttribLocation(program,"color");

const mvpLocation =
    gl.getUniformLocation(program,"mvp");

gl.enable(gl.DEPTH_TEST);

/*
Tắt CULL_FACE để tránh mất mặt
do thứ tự tam giác của các vật thể.
*/

gl.disable(gl.CULL_FACE);

gl.clearColor(0.61,0.79,0.91,1);

/* =====================================
   MATRIX ENGINE
===================================== */

function perspective(fov,aspect,near,far){
    const f = 1 / Math.tan(fov/2);
    const nf = 1 / (near-far);

    return [
        f/aspect,0,0,0,
        0,f,0,0,
        0,0,(far+near)*nf,-1,
        0,0,2*far*near*nf,0
    ];
}

function dot(a,b){
    return a[0]*b[0]+a[1]*b[1]+a[2]*b[2];
}

function cross(a,b){
    return [
        a[1]*b[2]-a[2]*b[1],
        a[2]*b[0]-a[0]*b[2],
        a[0]*b[1]-a[1]*b[0]
    ];
}

function normalize(v){
    const l = Math.hypot(v[0],v[1],v[2]) || 1;

    return [
        v[0]/l,
        v[1]/l,
        v[2]/l
    ];
}

function lookAt(eye,target,up){
    const z = normalize([
        eye[0]-target[0],
        eye[1]-target[1],
        eye[2]-target[2]
    ]);

    const x = normalize(cross(up,z));
    const y = cross(z,x);

    return [
        x[0],y[0],z[0],0,
        x[1],y[1],z[1],0,
        x[2],y[2],z[2],0,
        -dot(x,eye),-dot(y,eye),-dot(z,eye),1
    ];
}

function multiply(a,b){
    const out = new Array(16);

    for(let c=0;c<4;c++){
        for(let r=0;r<4;r++){
            out[c*4+r] =
                a[r]*b[c*4] +
                a[4+r]*b[c*4+1] +
                a[8+r]*b[c*4+2] +
                a[12+r]*b[c*4+3];
        }
    }

    return out;
}

/* =====================================
   GEOMETRY ENGINE
===================================== */

let vertices = [];
let colors = [];

/*
OX và OZ cho phép dịch chuyển
từng vật thể mà không phải viết lại
toàn bộ hàm hình học.
*/

let OX = 0;
let OZ = 0;

function setOffset(x,z){
    OX = x;
    OZ = z;
}

function triangle(a,b,c,color){
    for(const p of [a,b,c]){
        vertices.push(
            p[0]+OX,
            p[1],
            p[2]+OZ
        );

        colors.push(
            color[0],
            color[1],
            color[2]
        );
    }
}

function quad(a,b,c,d,color){
    triangle(a,b,c,color);
    triangle(a,c,d,color);
}

function shade(c,f){
    return [
        Math.min(1,c[0]*f),
        Math.min(1,c[1]*f),
        Math.min(1,c[2]*f)
    ];
}

function box(x,y,z,w,h,d,color){
    const x1=x-w/2;
    const x2=x+w/2;
    const y1=y-h/2;
    const y2=y+h/2;
    const z1=z-d/2;
    const z2=z+d/2;

    quad(
        [x1,y1,z2],[x2,y1,z2],
        [x2,y2,z2],[x1,y2,z2],
        color
    );

    quad(
        [x2,y1,z1],[x1,y1,z1],
        [x1,y2,z1],[x2,y2,z1],
        shade(color,.75)
    );

    quad(
        [x1,y2,z2],[x2,y2,z2],
        [x2,y2,z1],[x1,y2,z1],
        shade(color,1.15)
    );

    quad(
        [x1,y1,z1],[x2,y1,z1],
        [x2,y1,z2],[x1,y1,z2],
        shade(color,.65)
    );

    quad(
        [x2,y1,z2],[x2,y1,z1],
        [x2,y2,z1],[x2,y2,z2],
        shade(color,.85)
    );

    quad(
        [x1,y1,z1],[x1,y1,z2],
        [x1,y2,z2],[x1,y2,z1],
        shade(color,.9)
    );
}

function sphere(cx,cy,cz,r,color){
    const rings=10;
    const sides=14;

    for(let i=0;i<rings;i++){
        const a1=Math.PI*i/rings;
        const a2=Math.PI*(i+1)/rings;

        for(let j=0;j<sides;j++){
            const b1=2*Math.PI*j/sides;
            const b2=2*Math.PI*(j+1)/sides;

            function point(a,b){
                return [
                    cx+r*Math.sin(a)*Math.cos(b),
                    cy+r*Math.cos(a),
                    cz+r*Math.sin(a)*Math.sin(b)
                ];
            }

            const p1=point(a1,b1);
            const p2=point(a2,b1);
            const p3=point(a2,b2);
            const p4=point(a1,b2);

            triangle(p1,p2,p3,color);
            triangle(p1,p3,p4,color);
        }
    }
}

function cylinder(x,y,z,r,h,color,sides=12){
    const bottom=y;
    const top=y+h;

    for(let i=0;i<sides;i++){
        const a=i*2*Math.PI/sides;
        const b=(i+1)*2*Math.PI/sides;

        const p1=[
            x+r*Math.cos(a),
            bottom,
            z+r*Math.sin(a)
        ];

        const p2=[
            x+r*Math.cos(b),
            bottom,
            z+r*Math.sin(b)
        ];

        const p3=[
            x+r*Math.cos(b),
            top,
            z+r*Math.sin(b)
        ];

        const p4=[
            x+r*Math.cos(a),
            top,
            z+r*Math.sin(a)
        ];

        quad(p1,p2,p3,p4,color);

        triangle(
            [x,bottom,z],p2,p1,
            shade(color,.7)
        );

        triangle(
            [x,top,z],p3,p4,
            shade(color,1.2)
        );
    }
}

function cone(x,y,z,r,h,color,sides=14){
    for(let i=0;i<sides;i++){
        const a=i*2*Math.PI/sides;
        const b=(i+1)*2*Math.PI/sides;

        const p1=[
            x+r*Math.cos(a),
            y,
            z+r*Math.sin(a)
        ];

        const p2=[
            x+r*Math.cos(b),
            y,
            z+r*Math.sin(b)
        ];

        triangle(
            p1,p2,[x,y+h,z],
            shade(color,1.1)
        );
    }
}

function segment(a,b,r,color){
    const direction=normalize([
        b[0]-a[0],
        b[1]-a[1],
        b[2]-a[2]
    ]);

    let u=normalize(cross(direction,[0,0,1]));

    if(Math.hypot(...u)<0.01){
        u=normalize(cross(direction,[1,0,0]));
    }

    const v=normalize(cross(direction,u));
    const sides=8;

    for(let i=0;i<sides;i++){
        const t1=i*2*Math.PI/sides;
        const t2=(i+1)*2*Math.PI/sides;

        function point(t,end){
            const center=end?b:a;

            return [
                center[0]+r*(
                    u[0]*Math.cos(t)+v[0]*Math.sin(t)
                ),
                center[1]+r*(
                    u[1]*Math.cos(t)+v[1]*Math.sin(t)
                ),
                center[2]+r*(
                    u[2]*Math.cos(t)+v[2]*Math.sin(t)
                )
            ];
        }

        quad(
            point(t1,false),
            point(t2,false),
            point(t2,true),
            point(t1,true),
            color
        );
    }
}

/* =====================================
   PROMPT ENGINE
===================================== */

function normalizeText(s){
    return s.toLowerCase().normalize("NFC");
}

function hasAny(s,words){
    return words.some(w=>s.includes(w));
}

/*
Đọc số lượng vật thể.
Ví dụ:
2 nhà
3 ngôi nhà
4 robot
5 người que

Mỗi loại tối đa 10 vật thể.
*/

function countObject(prompt,words){
    for(const word of words){
        const escaped = word.replace(
            /[.*+?^${}()|[\]\\]/g,
            "\\$&"
        );

        const regex = new RegExp(
            "(\\d+)\\s*" + escaped,
            "u"
        );

        const match = prompt.match(regex);

        if(match){
            const number = parseInt(match[1],10);

            return Math.max(
                1,
                Math.min(10,number)
            );
        }
    }

    return 1;
}

/* =====================================
   OBJECT GENERATORS
===================================== */

function makeHouse(color){
    // Thân nhà
    box(0,1,0,2.8,2,2.4,[.85,.67,.42]);

    // Mái nhà
    const front=[0,3,1.2];
    const back=[0,3,-1.2];

    const leftFront=[-1.8,1.9,1.2];
    const rightFront=[1.8,1.9,1.2];

    const leftBack=[-1.8,1.9,-1.2];
    const rightBack=[1.8,1.9,-1.2];

    triangle(leftFront,front,rightFront,color);
    triangle(leftBack,rightBack,back,shade(color,.8));

    quad(
        leftFront,leftBack,back,front,
        shade(color,.9)
    );

    quad(
        front,back,rightBack,rightFront,
        shade(color,1.1)
    );

    // Cửa
    box(0,.65,1.22,.65,1.3,.08,[.35,.17,.08]);

    // Cửa sổ
    box(-.9,1.35,1.23,.5,.5,.08,[.12,.45,.9]);
    box(.9,1.35,1.23,.5,.5,.08,[.12,.45,.9]);
}

function makeTree(){
    cylinder(0,0,0,.18,1.2,[.43,.23,.1]);

    cone(0,.9,0,.8,1.5,[.12,.62,.22]);
    cone(0,1.65,0,.58,1.25,[.1,.55,.18]);

    sphere(0,2.35,0,.3,[.16,.7,.22]);
}

function makeRobot(color){
    // Thân
    box(0,1.35,0,1.1,1.3,.8,color);

    // Đầu
    box(0,2.4,0,.85,.75,.75,shade(color,1.15));

    // Mắt
    sphere(-.2,2.48,.4,.1,[1,1,1]);
    sphere(.2,2.48,.4,.1,[1,1,1]);

    // Miệng
    box(0,2.22,.4,.3,.06,.04,[.08,.08,.08]);

    // Tay
    segment(
        [-.55,1.7,0],
        [-.95,.95,0],
        .14,[.08,.1,.13]
    );

    segment(
        [.55,1.7,0],
        [.95,.95,0],
        .14,[.08,.1,.13]
    );

    // Chân
    segment(
        [-.25,.7,0],
        [-.35,.05,0],
        .17,[.08,.1,.13]
    );

    segment(
        [.25,.7,0],
        [.35,.05,0],
        .17,[.08,.1,.13]
    );
}

function makeStickman(color){
    sphere(0,2,0,.23,color);

    segment(
        [0,1.8,0],
        [0,1,0],
        .075,color
    );

    segment(
        [0,1.6,0],
        [-.4,1.25,0],
        .06,color
    );

    segment(
        [0,1.6,0],
        [.4,1.25,0],
        .06,color
    );

    segment(
        [0,1,0],
        [-.3,.05,0],
        .07,color
    );

    segment(
        [0,1,0],
        [.3,.05,0],
        .07,color
    );
}

function makeCloud(color){
    sphere(-.5,0,0,.5,color);
    sphere(0,.2,0,.7,color);
    sphere(.55,0,0,.5,color);
}

function makeSun(){
    sphere(0,0,0,.8,[1,.75,.08]);
}

/* =====================================
   SCENE GENERATOR
===================================== */

let scenePositionBuffer=null;
let sceneColorBuffer=null;
let sceneVertexCount=0;

let cameraDistance=15;

function generateScene(){
    vertices=[];
    colors=[];

    const p=normalizeText(PROMPT);

    const red=[.9,.12,.12];
    const blue=[.12,.35,.9];
    const yellow=[1,.75,.08];
    const purple=[.55,.2,.8];
    const white=[.9,.92,.96];
    const black=[.08,.1,.13];

    let mainColor=blue;

    if(p.includes("đỏ")) mainColor=red;
    if(p.includes("vàng")) mainColor=yellow;
    if(p.includes("tím")) mainColor=purple;
    if(p.includes("trắng")) mainColor=white;
    if(p.includes("đen")) mainColor=black;

    /*
    Xác định loại vật thể
    và số lượng cần tạo.
    */

    const objects=[];

    if(hasAny(p,["nhà","ngôi nhà","house"])){
        const n=countObject(
            p,
            ["ngôi nhà","căn nhà","nhà","house"]
        );

        for(let i=0;i<n;i++){
            objects.push({
                type:"house",
                width:3.2,
                height:3
            });
        }
    }

    if(hasAny(p,["cây","tree","rừng"])){
        const n=countObject(
            p,
            ["cây","tree"]
        );

        for(let i=0;i<n;i++){
            objects.push({
                type:"tree",
                width:2.2,
                height:3
            });
        }
    }

    if(hasAny(p,["robot","người máy"])){
        const n=countObject(
            p,
            ["robot","người máy"]
        );

        for(let i=0;i<n;i++){
            objects.push({
                type:"robot",
                width:2.1,
                height:2.8
            });
        }
    }

    if(hasAny(p,["người que","stickman"])){
        const n=countObject(
            p,
            ["người que","stickman"]
        );

        for(let i=0;i<n;i++){
            objects.push({
                type:"stickman",
                width:1.4,
                height:2.2
            });
        }
    }

    if(hasAny(p,["mây","cloud"])){
        const n=countObject(
            p,
            ["đám mây","mây","cloud"]
        );

        for(let i=0;i<n;i++){
            objects.push({
                type:"cloud",
                width:2.5,
                height:1.5
            });
        }
    }

    /*
    Bố trí vật thể theo lưới 4 cột.
    Tất cả loại vật thể dùng chung
    một bộ đếm vị trí để giảm chồng lấn.
    */

    const columns=4;
    const spacing=4;

    const total=objects.length;
    const rows=Math.max(1,Math.ceil(total/columns));

    for(let i=0;i<objects.length;i++){
        const object=objects[i];

        const column=i%columns;
        const row=Math.floor(i/columns);

        const x=(column-(columns-1)/2)*spacing;
        const z=(row-(rows-1)/2)*spacing;

        setOffset(x,z);

        if(object.type==="house"){
            makeHouse(
                p.includes("mái xanh")
                    ? green
                    : red
            );
        }

        if(object.type==="tree"){
            makeTree();
        }

        if(object.type==="robot"){
            makeRobot(mainColor);
        }

        if(object.type==="stickman"){
            makeStickman(mainColor);
        }

        if(object.type==="cloud"){
            makeCloud(white);
        }
    }

    /*
    Mặt trời đặt ở phía trên,
    không chiếm vị trí trong lưới.
    */

    if(hasAny(p,["mặt trời","sun","ban ngày"])){
        setOffset(-6,-6);
        makeSun();
    }

    /*
    Tạo mặt đất theo kích thước cảnh.
    */

    setOffset(0,0);

    const groundWidth=Math.max(16,columns*spacing+2);
    const groundDepth=Math.max(16,rows*spacing+2);

    box(
        0,-.2,0,
        groundWidth,.25,groundDepth,
        [.3,.55,.28]
    );

    /*
    Tải dữ liệu lên GPU.
    */

    scenePositionBuffer=gl.createBuffer();

    gl.bindBuffer(
        gl.ARRAY_BUFFER,
        scenePositionBuffer
    );

    gl.bufferData(
        gl.ARRAY_BUFFER,
        new Float32Array(vertices),
        gl.STATIC_DRAW
    );

    sceneColorBuffer=gl.createBuffer();

    gl.bindBuffer(
        gl.ARRAY_BUFFER,
        sceneColorBuffer
    );

    gl.bufferData(
        gl.ARRAY_BUFFER,
        new Float32Array(colors),
        gl.STATIC_DRAW
    );

    sceneVertexCount=vertices.length/3;

    /*
    Tự điều chỉnh camera theo số hàng.
    */

    cameraDistance=Math.max(
        15,
        Math.min(40,12+rows*3)
    );

    return {
        total:objects.length,
        rows:rows
    };
}

/* =====================================
   CAMERA
===================================== */

let angle=.75;
let elevation=.45;
let distance=15;

function resetCamera(){
    angle=.75;
    elevation=.45;
    distance=cameraDistance;
    render();
}

function zoom(factor){
    distance=Math.max(
        4,
        Math.min(45,distance*factor)
    );

    render();
}

/* =====================================
   RENDER ENGINE
===================================== */

function render(){
    const width=canvas.clientWidth;
    const height=canvas.clientHeight;

    if(width<=0 || height<=0) return;

    const dpr=Math.min(
        window.devicePixelRatio||1,
        2
    );

    const newWidth=Math.round(width*dpr);
    const newHeight=Math.round(height*dpr);

    if(canvas.width!==newWidth ||
       canvas.height!==newHeight){

        canvas.width=newWidth;
        canvas.height=newHeight;
    }

    gl.viewport(
        0,0,
        canvas.width,
        canvas.height
    );

    gl.clear(
        gl.COLOR_BUFFER_BIT |
        gl.DEPTH_BUFFER_BIT
    );

    const eye=[
        distance*Math.sin(angle)*Math.cos(elevation),
        distance*Math.sin(elevation),
        distance*Math.cos(angle)*Math.cos(elevation)
    ];

    const view=lookAt(
        eye,
        [0,1,0],
        [0,1,0]
    );

    const projection=perspective(
        Math.PI/4,
        canvas.width/canvas.height,
        .1,
        150
    );

    const mvp=multiply(projection,view);

    gl.uniformMatrix4fv(
        mvpLocation,
        false,
        new Float32Array(mvp)
    );

    gl.bindBuffer(
        gl.ARRAY_BUFFER,
        scenePositionBuffer
    );

    gl.enableVertexAttribArray(positionLocation);

    gl.vertexAttribPointer(
        positionLocation,
        3,
        gl.FLOAT,
        false,
        0,
        0
    );

    gl.bindBuffer(
        gl.ARRAY_BUFFER,
        sceneColorBuffer
    );

    gl.enableVertexAttribArray(colorLocation);

    gl.vertexAttribPointer(
        colorLocation,
        3,
        gl.FLOAT,
        false,
        0,
        0
    );

    gl.drawArrays(
        gl.TRIANGLES,
        0,
        sceneVertexCount
    );
}

/* =====================================
   TOUCH AND MOUSE
===================================== */

let dragging=false;
let lastX=0;
let lastY=0;

canvas.addEventListener("pointerdown",e=>{
    dragging=true;

    lastX=e.clientX;
    lastY=e.clientY;

    canvas.setPointerCapture(e.pointerId);
});

canvas.addEventListener("pointermove",e=>{
    if(!dragging)return;

    const dx=e.clientX-lastX;
    const dy=e.clientY-lastY;

    angle+=dx*.01;

    elevation=Math.max(
        -1.2,
        Math.min(1.2,elevation+dy*.008)
    );

    lastX=e.clientX;
    lastY=e.clientY;

    render();
});

canvas.addEventListener("pointerup",()=>{
    dragging=false;
});

canvas.addEventListener("pointercancel",()=>{
    dragging=false;
});

canvas.addEventListener("wheel",e=>{
    e.preventDefault();

    zoom(e.deltaY>0?1.1:.9);
},{passive:false});

/* =====================================
   EXPORT PNG
===================================== */

function saveImage(){
    try{
        render();

        const link=document.createElement("a");

        link.download="codebrain-3d.png";

        link.href=canvas.toDataURL("image/png");

        link.click();

    }catch(e){
        alert("Không thể xuất PNG: "+e.message);
    }
}

/* =====================================
   START
===================================== */

try{
    const result=generateScene();

    distance=cameraDistance;

    render();

    statusText.textContent=
        "WebGL hoạt động • " +
        result.total +
        " vật thể • " +
        sceneVertexCount +
        " đỉnh 3D • Offline";

}catch(error){
    statusText.textContent="Lỗi: "+error.message;
    console.error(error);
}

window.addEventListener("resize",render);

</script>
</body>
</html>
