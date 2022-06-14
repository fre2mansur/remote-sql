/**
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */
function md5(s){return rstr2hex(rstr_md5(str2rstr_utf8(s)));}
/* Calculate the MD5 of a raw string */
function rstr_md5(s){return binl2rstr(binl_md5(rstr2binl(s),s.length*8));}
/* Convert an array of little-endian words to a string */
function binl2rstr(input){
  var output='';
  for(var i=0;i<input.length*32;i +=8) output +=String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
  return output;
}
/* Calculate the MD5 of an array of little-endian words, and a bit length */
function binl_md5(x,len){/*append padding*/
  x[len >> 5] |= 0x80 << ((len) % 32);
  x[(((len+64) >>> 9) << 4)+14]=len;
  var a=1732584193, b=-271733879, c=-1732584194, d=271733878;
  for(var i=0;i<x.length;i +=16){
    var olda=a, oldb=b, oldc=c, oldd=d;
    a=md5_ff(a,b,c,d,x[i+ 0],7 ,-680876936);
    d=md5_ff(d,a,b,c,x[i+ 1],12,-389564586);
    c=md5_ff(c,d,a,b,x[i+ 2],17,606105819);
    b=md5_ff(b,c,d,a,x[i+ 3],22,-1044525330);
    a=md5_ff(a,b,c,d,x[i+ 4],7 ,-176418897);
    d=md5_ff(d,a,b,c,x[i+ 5],12,1200080426);
    c=md5_ff(c,d,a,b,x[i+ 6],17,-1473231341);
    b=md5_ff(b,c,d,a,x[i+ 7],22,-45705983);
    a=md5_ff(a,b,c,d,x[i+ 8],7 ,1770035416);
    d=md5_ff(d,a,b,c,x[i+ 9],12,-1958414417);
    c=md5_ff(c,d,a,b,x[i+10],17,-42063);
    b=md5_ff(b,c,d,a,x[i+11],22,-1990404162);
    a=md5_ff(a,b,c,d,x[i+12],7 ,1804603682);
    d=md5_ff(d,a,b,c,x[i+13],12,-40341101);
    c=md5_ff(c,d,a,b,x[i+14],17,-1502002290);
    b=md5_ff(b,c,d,a,x[i+15],22,1236535329);
    a=md5_gg(a,b,c,d,x[i+ 1],5 ,-165796510);
    d=md5_gg(d,a,b,c,x[i+ 6],9 ,-1069501632);
    c=md5_gg(c,d,a,b,x[i+11],14,643717713);
    b=md5_gg(b,c,d,a,x[i+ 0],20,-373897302);
    a=md5_gg(a,b,c,d,x[i+ 5],5 ,-701558691);
    d=md5_gg(d,a,b,c,x[i+10],9 ,38016083);
    c=md5_gg(c,d,a,b,x[i+15],14,-660478335);
    b=md5_gg(b,c,d,a,x[i+ 4],20,-405537848);
    a=md5_gg(a,b,c,d,x[i+ 9],5 ,568446438);
    d=md5_gg(d,a,b,c,x[i+14],9 ,-1019803690);
    c=md5_gg(c,d,a,b,x[i+ 3],14,-187363961);
    b=md5_gg(b,c,d,a,x[i+ 8],20,1163531501);
    a=md5_gg(a,b,c,d,x[i+13],5 ,-1444681467);
    d=md5_gg(d,a,b,c,x[i+ 2],9 ,-51403784);
    c=md5_gg(c,d,a,b,x[i+ 7],14,1735328473);
    b=md5_gg(b,c,d,a,x[i+12],20,-1926607734);
    a=md5_hh(a,b,c,d,x[i+ 5],4 ,-378558);
    d=md5_hh(d,a,b,c,x[i+ 8],11,-2022574463);
    c=md5_hh(c,d,a,b,x[i+11],16,1839030562);
    b=md5_hh(b,c,d,a,x[i+14],23,-35309556);
    a=md5_hh(a,b,c,d,x[i+ 1],4 ,-1530992060);
    d=md5_hh(d,a,b,c,x[i+ 4],11,1272893353);
    c=md5_hh(c,d,a,b,x[i+ 7],16,-155497632);
    b=md5_hh(b,c,d,a,x[i+10],23,-1094730640);
    a=md5_hh(a,b,c,d,x[i+13],4 ,681279174);
    d=md5_hh(d,a,b,c,x[i+ 0],11,-358537222);
    c=md5_hh(c,d,a,b,x[i+ 3],16,-722521979);
    b=md5_hh(b,c,d,a,x[i+ 6],23,76029189);
    a=md5_hh(a,b,c,d,x[i+ 9],4 ,-640364487);
    d=md5_hh(d,a,b,c,x[i+12],11,-421815835);
    c=md5_hh(c,d,a,b,x[i+15],16,530742520);
    b=md5_hh(b,c,d,a,x[i+ 2],23,-995338651);
    a=md5_ii(a,b,c,d,x[i+ 0],6 ,-198630844);
    d=md5_ii(d,a,b,c,x[i+ 7],10,1126891415);
    c=md5_ii(c,d,a,b,x[i+14],15,-1416354905);
    b=md5_ii(b,c,d,a,x[i+ 5],21,-57434055);
    a=md5_ii(a,b,c,d,x[i+12],6 ,1700485571);
    d=md5_ii(d,a,b,c,x[i+ 3],10,-1894986606);
    c=md5_ii(c,d,a,b,x[i+10],15,-1051523);
    b=md5_ii(b,c,d,a,x[i+ 1],21,-2054922799);
    a=md5_ii(a,b,c,d,x[i+ 8],6 ,1873313359);
    d=md5_ii(d,a,b,c,x[i+15],10,-30611744);
    c=md5_ii(c,d,a,b,x[i+ 6],15,-1560198380);
    b=md5_ii(b,c,d,a,x[i+13],21,1309151649);
    a=md5_ii(a,b,c,d,x[i+ 4],6 ,-145523070);
    d=md5_ii(d,a,b,c,x[i+11],10,-1120210379);
    c=md5_ii(c,d,a,b,x[i+ 2],15,718787259);
    b=md5_ii(b,c,d,a,x[i+ 9],21,-343485551);
    a=safe_add(a,olda);
    b=safe_add(b,oldb);
    c=safe_add(c,oldc);
    d=safe_add(d,oldd);
  }
  return Array(a,b,c,d);
}
/* These functions implement the four basic operations the algorithm uses */
function md5_cmn(q,a,b,x,s,t){return safe_add(bit_rol(safe_add(safe_add(a,q),safe_add(x,t)),s),b);}
function md5_ff(a,b,c,d,x,s,t){return md5_cmn((b & c) | ((~b) & d),a,b,x,s,t);}
function md5_gg(a,b,c,d,x,s,t){return md5_cmn((b & d) | (c & (~d)),a,b,x,s,t);}
function md5_hh(a,b,c,d,x,s,t){return md5_cmn(b ^ c ^ d,a,b,x,s,t);}
function md5_ii(a,b,c,d,x,s,t){return md5_cmn(c ^ (b | (~d)),a,b,x,s,t);}
/* Bitwise rotate a 32-bit number to the left */
function bit_rol(num,cnt){return (num << cnt) | (num >>> (32 - cnt));}
/* Convert a raw string to an array of little-endian words Chars>255 have their high-byte silently ignored */
function rstr2binl(input){
  var output=Array(input.length >> 2);
  for(var i=0;i<output.length;i++) output[i]=0;
  for(var i=0;i<input.length*8;i +=8) output[i>>5] |= (input.charCodeAt(i/8) & 0xFF) << (i%32);
  return output;
}
/* the following 3 fn also used by sha256.js */
/* Add int, wrapping at 2^32. uses 16-bit operations internally to work around bugs in some JS interpreters */
function safe_add(x,y){
  var lsw=(x & 0xFFFF)+(y & 0xFFFF);
  var msw=(x >> 16)+(y >> 16)+(lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}
function str2rstr_utf8(input){
  var output='', i=-1, x, y;
  while(++i <input.length){
    /* Decode utf-16 surrogate pairs */
    x=input.charCodeAt(i);
    y=i+1 < input.length ? input.charCodeAt(i + 1) : 0;
    if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF){
      x=0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
      i++;
    }
    /* Encode output as utf-8 */
    if(x <= 0x7F) output +=String.fromCharCode(x);
    else if(x <= 0x7FF) output +=String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F), 0x80 | (x & 0x3F));
    else if(x <= 0xFFFF) output +=String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F), 0x80 | ((x >>> 6 ) & 0x3F), 0x80 | (x & 0x3F));
    else if(x <= 0x1FFFFF) output +=String.fromCharCode(0xF0 | ((x >>> 18) & 0x07), 0x80 | ((x >>> 12) & 0x3F), 0x80 | ((x >>> 6 ) & 0x3F), 0x80 | (x & 0x3F));
  }
  return output;
}
function rstr2hex(input){/*convert raw str to hex str*/
  try {hexcase} catch(e){hexcase=0;}
  var hex_tab='0123456789abcdef', output='', x;
  for(var i=0;i<input.length;i++){
    x=input.charCodeAt(i);
    output +=hex_tab.charAt((x >>> 4) & 0x0F) + hex_tab.charAt(x & 0x0F);
  }
  return output;
}

/* ----the following is sha256.js------- */

/* ==need md5.js for: safe_add(x,y) rstr2hex(input) str2rstr_utf8(input)=== */
/* A JavaScript implementation of the Secure Hash Algorithm, SHA-256, as defined
 * in FIPS 180-2
 * Version 2.2 Copyright Angel Marin, Paul Johnston 2000 - 2009.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */
function sha256(s){return rstr2hex(rstr_sha256(str2rstr_utf8(s)));}
/* Calculate the sha256 of a raw string */
function rstr_sha256(s){return binb2rstr(binb_sha256(rstr2binb(s),s.length*8));}
/* raw str to array of big-endian words Char>255 have their high-byte silently ignored. */
function rstr2binb(input){
  var output=Array(input.length >> 2);
  for(var i=0;i<output.length;i++) output[i]=0;
  for(var i=0;i<input.length*8;i +=8) output[i>>5] |= (input.charCodeAt(i/8) & 0xFF) << (24-i%32);
  return output;
}
/* Convert an array of big-endian words to a string */
function binb2rstr(input){
  var output='';
  for(var i=0;i<input.length*32;i +=8) output +=String.fromCharCode((input[i>>5] >>> (24-i%32)) & 0xFF);
  return output;
}
/* Main sha256 function, with its support functions */
function sha256_S (X,n){return (X >>> n ) | (X << (32-n));}
function sha256_R (X,n){return (X >>> n );}
function sha256_Ch(x,y,z){return ((x & y) ^ ((~x) & z));}
function sha256_Maj(x,y,z){return ((x & y) ^ (x & z) ^ (y & z));}
function sha256_Sigma0256(x){return (sha256_S(x, 2) ^ sha256_S(x,13) ^ sha256_S(x,22));}
function sha256_Sigma1256(x){return (sha256_S(x, 6) ^ sha256_S(x,11) ^ sha256_S(x,25));}
function sha256_Gamma0256(x){return (sha256_S(x, 7) ^ sha256_S(x,18) ^ sha256_R(x, 3));}
function sha256_Gamma1256(x){return (sha256_S(x,17) ^ sha256_S(x,19) ^ sha256_R(x,10));}
function sha256_Sigma0512(x){return (sha256_S(x,28) ^ sha256_S(x,34) ^ sha256_S(x,39));}
function sha256_Sigma1512(x){return (sha256_S(x,14) ^ sha256_S(x,18) ^ sha256_S(x,41));}
function sha256_Gamma0512(x){return (sha256_S(x, 1)  ^ sha256_S(x, 8) ^ sha256_R(x, 7));}
function sha256_Gamma1512(x){return (sha256_S(x,19) ^ sha256_S(x,61) ^ sha256_R(x, 6));}
var sha256_K=new Array(1116352408,1899447441,-1245643825,-373957723,961987163,1508970993,-1841331548,-1424204075,-670586216,310598401,607225278,1426881987,1925078388,-2132889090,-1680079193,-1046744716,-459576895,-272742522,264347078,604807628,770255983,1249150122,1555081692,1996064986,-1740746414,-1473132947,-1341970488,-1084653625,-958395405,-710438585,113926993,338241895,666307205,773529912,1294757372,1396182291,1695183700,1986661051,-2117940946,-1838011259,-1564481375,-1474664885,-1035236496,-949202525,-778901479,-694614492,-200395387,275423344,430227734,506948616,659060556,883997877,958139571,1322822218,1537002063,1747873779,1955562222,2024104815,-2067236844,-1933114872,-1866530822,-1538233109,-1090935817,-965641998);
function binb_sha256(m,l){
  var HASH=new Array(1779033703,-1150833019,1013904242,-1521486534,1359893119,-1694144372,528734635,1541459225);
  var W=new Array(64);
  var a,b,c,d,e,f,g,h;
  var i,j,T1,T2;
  /* append padding */
  m[l >> 5] |= 0x80 << (24-l%32);
  m[((l+64 >> 9) << 4) +15]=l;
  for(i=0;i<m.length;i +=16){
    a=HASH[0]; b=HASH[1]; c=HASH[2]; d=HASH[3];
    e=HASH[4]; f=HASH[5]; g=HASH[6]; h=HASH[7];
    for(j=0;j<64;j++){
      if (j<16) W[j]=m[j+i];
      else W[j]=safe_add(safe_add(safe_add(sha256_Gamma1256(W[j-2]),W[j-7]),sha256_Gamma0256(W[j-15])),W[j-16]);
      T1=safe_add(safe_add(safe_add(safe_add(h,sha256_Sigma1256(e)),sha256_Ch(e,f,g)),sha256_K[j]),W[j]);
      T2=safe_add(sha256_Sigma0256(a),sha256_Maj(a,b,c));
      h=g; g=f; f=e; e=safe_add(d,T1);
      d=c; c=b; b=a; a=safe_add(T1,T2);
    }
    HASH[0]=safe_add(a,HASH[0]); HASH[1]=safe_add(b,HASH[1]);
    HASH[2]=safe_add(c,HASH[2]); HASH[3]=safe_add(d,HASH[3]);
    HASH[4]=safe_add(e,HASH[4]); HASH[5]=safe_add(f,HASH[5]);
    HASH[6]=safe_add(g,HASH[6]); HASH[7]=safe_add(h,HASH[7]);
  }
  return HASH;
}
