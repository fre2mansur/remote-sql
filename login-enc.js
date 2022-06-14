//make sure html head include:
//<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
//jQuery use .text() instead of .html() to get str
//----need md5.js-----------------------
//1.cms_enc(str,key) + cms_dec(str,key)
function cms_enc(str,key){
  var base=88;
  var n1=rand(0,65535);
  var n2=rand(0,65535);
  var n3=rand(0,255);
  var n4=rand(0,255);
  var n5=rand(0,255);
  var code=md5('南無阿彌陀佛 - Namo Amitabha - 南无阿弥陀佛'+key);
  //code+=navigator.userAgent;//diff fm php version,not good idea
  code=md5(code);
  var name=md5(code);
  var hw='ABCD1234';
  return cms_kg(9,code,name,hw,n1,n2,n3,n4,n5,str,base);
}
function cms_kg(level,code,name,hw,n1,n2,n3,n4,n5,str,base){
  //rand 2 date 2 HW 4 n1 2 n2 2 n3 1 n4 1 n5 1 str 9 = 24char max internal = 48char max output |L9 no limit
  var res=cms_kg_now()+cms_kg_hw(hw)+pad0(cms_kg_b256(n1%65536),6)+pad0(cms_kg_b256(n2%65536),6)+cms_kg_b256(n3%256)+cms_kg_b256(n4%256)+cms_kg_b256(n5%256)+cms_kg_str(str,level);
  return cms_kg_txt(res,level,code,name,base);
}
//the following all private func
function cms_kg_txt(txt,level,code,name,base){
  code=cms_kg_code(code);
  var arr=cms_kg_codeArr(code,name);
  var md5=cms_kg_md5txt(txt,code,name);
  txt=str_split(txt,3);
  txt[0]=md5;//used for err checking
  txt=cms_kg_add(txt,arr,1);
  txt=cms_kg_add(txt,arr,-1);
  txt=cms_kg_add(txt,arr,2);
  txt=cms_kg_add(txt,arr,-2);
  txt=cms_kg_add(txt,arr,3);
  txt=cms_kg_add(txt,arr,-3);
  txt=cms_kg_add(txt,arr,1);
  return cms_kg_cout(txt,base);
}
function cms_kg_now(){//rand.now:123456.123456
  var now=Math.floor((time()-946645200)/86400);//max 179 year from year 2000.01.01 00:00:00
  return pad0(cms_kg_b256(rand(0,65535)),6)+pad0(cms_kg_b256(now),6);
}
function cms_kg_hw(hw){//max ffffffff no-
  var res='';
  for (var i=0;i<4;i++) res +=pad0(parseInt(hw.substr(i*2,2),16));
  return res;
}
function cms_kg_add(txt,arr,x){
  var y=Math.abs(x);//1 2 3
  var add=(x<0 ? -1 : 1);
  var rand=str_split(txt[0]+''+txt[1],y);
  var numR=rand.length;
  var numT=txt.length-2;
  var numA=arr.length;
  for (var k in txt){
    pos=Math.abs(parseInt(k)+rand[k%numR]*add)%numT+2;
    //255+7*106=997max::32*32=1024
    if (k>1) txt[k]=parseInt(txt[k],10)+arr[pos%numA];
  }
  for (k in txt){
    pos=Math.abs(parseInt(k)+rand[k%numR]*add)%numT+2;
    if (k>1){
      var t=txt[k]; txt[k]=txt[pos]; txt[pos]=t;
    }
  }
  return txt;
}
function cms_kg_base(x,dec){
  var b32='23456789ABCDEFGHJKLMNPQRSTUVWXYZ';//good for licence
  var b64=b32+'abcdefghijkmnpqrstuvwxyz01IOlo_-';//good for cookie
  var b88=b64+'~!@#$%^&*()+`={}|[]:;?,.';//except '"<>/\ from 94keyboard
  if (dec) return b88.indexOf(x);
  return b88.charAt(x);
}
function cms_kg_b256(num){//max 4294967295
  num=num.toString(2);
  var len=Math.ceil(num.length/8)*8;
  num=pad0(num,len);
  var res='';
  for (var i=0;i<len;i +=8) res +=pad0(parseInt(num.substr(i,8),2));
  return res;
}
function rand(min,max){
  if (!min) min=0;
  if (!max) max=2147483647;
  return Math.floor(Math.random()*(max-min+1))+min;
}
function pad0(s,len){
  if (!len) len=3;
  s=s.toString();
  if (s.length<len) return pad0('0'+s,len);
  return s;
}
function cms_kg_str(str,level){
  str=str.replace(/^\s+|\s+$/g,'');//trim
  if (str=='') str=md5(time());
  if (level<9) str=str.substr(0,9);
  var len=str.length;
  var res='';
  for (var i=0;i<len;i++){
    var x=str.charCodeAt(i);
    if (x<256) res +=pad0(x);
    else res +=str2UTF8(str.charAt(i));
  }
  return res;
}
function time(){return Math.floor(new Date().getTime()/1000);}
function cms_kg_code(code){
  if (code==null) code=time();
  return md5(md5(code)+code).toUpperCase();
}
function cms_kg_codeArr(code,name){
  name=md5(name+md5(name));
  var arr=new Array(128);
  for (var i=0;i<32;i++){
    arr[i*4]=arr[i*4+2]=code.charCodeAt(i)%107;
    arr[i*4+1]=arr[i*4+3]=name.charCodeAt(i)%107;
  }//32*4=128
  return arr;
}
function cms_kg_md5txt(txt,code,name){
  var str=md5(code+md5(txt.substr(3))+md5(name));
  var res=0;
  for (var i=0;i<32;i++) res +=parseInt(str.charAt(i),16);
  return pad0(res);
}
function str_split(str,split_len){/*fm phpjs.org*/
  if (split_len===null) split_len=1;
  if (str===null || split_len<1) return false;
  str +='';
  var chunks=[],pos=0,len=str.length;
  while (pos<len) chunks.push(str.slice(pos,pos +=split_len));
  return chunks;
}
function cms_kg_cout(txt,base){//base32-88: x*x  =xxx
  var res='';
  for (var i in txt){
    var x=txt[i];
    var x2=Math.floor(x/base);
    var x3=x-x2*base;
    res +=cms_kg_base(x2)+cms_kg_base(x3);
  }
  return res;//base>62
}
function str2UTF8(str){//by http://rishida.net
  var highsurrogate=0;
  var suppCP;//decimal code point value for a supp char
  var n=0;
  var res='';
  for (var i=0;i<str.length;i++){
    cc=str.charCodeAt(i);
    if (highsurrogate!=0){
      if (0xDC00 <= cc && cc <= 0xDFFF){
        suppCP = 0x10000 + ((highsurrogate - 0xD800) << 10) + (cc - 0xDC00); 
        res +=pad0(0xF0 | ((suppCP>>18) & 0x07))+pad0(0x80 | ((suppCP>>12) & 0x3F))+pad0(0x80 | ((suppCP>>6) & 0x3F))+pad0(0x80 | (suppCP & 0x3F));
      }
      highsurrogate=0;
    }
    if (0xD800 <= cc && cc <= 0xDBFF) highsurrogate=cc;//high surrogate
    else{
      if (cc <= 0x7F) res +=pad0(cc);
      else if (cc <= 0x7FF) res +=pad0(0xC0 | ((cc>>6) & 0x1F))+pad0(0x80 | (cc & 0x3F));
      else if (cc <= 0xFFFF) res +=pad0(0xE0 | ((cc>>12) & 0x0F))+pad0(0x80 | ((cc>>6) & 0x3F))+pad0(0x80 | (cc & 0x3F));
    }
  }
  return res;
}
