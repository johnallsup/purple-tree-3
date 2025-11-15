/*
Parsedown turns
```abc
hello world
```
into
<pre><code class="language-abc">hello world</code></pre>
so we need to
querySelectorAll("pre code.language-abc")
and replace the parent with a div and render the ABC into it.


*/
window.addEventListener("load", _ => {
  const { log } = console
  const q = (x,y=document) => y.querySelector(x)
  const qq = (x,y=document) => Array.from(y.querySelectorAll(x))
  // const abcs = qq("pre code.language-abc")
  const abcs = qq("div.abc")
  log({msg:"hello world",abcs})
  abcs.forEach(div => {
    const abc = div.innerHTML.trim()
    div.innerHTML = "";
    ABCJS.renderAbc(div, abc, { responsive: "resize" });
  })
})
