class PTUIView extends PTUI {
  constructor(ajax) {
    super(ajax)
    this.signalCopyTime = 1500
  }
  setupUI() {
    super.setupUI()
    const { q, qq } = window
    function c(sel,cb) {
      qq(sel).forEach(elt => {
        elt.addEventListener("click",e => {
          e.preventDefault()
          cb(e)
        })
      })
    }
    c(".action.duplicate", e => this.duplicateView())
    c(".action.duplicate-edit",e => this.duplicateEdit())
    c(".action.versions",e => this.showVersions() )
    c(".action.show-goto-box",e => this.gotoBox.show() )
    c(".action.hamburger", e => this.toggleOptionsBar("hamburger"))
    c(".action.touch-mode", e => {
      document.body.classList.toggle("touch-mode")
    })
    // c(".action.more-actions", e => this.toggleOptionsBar("more-actions"))

    this.linksInBody = undefined
    this.currentLinkIndex = 0
    this.quickKeys = new Map()
    this.keys.next = e => {
      const k = e.key
      if( e.ctrlKey || e.altKey || e.shiftKey || e.metaKey ) return false
      if( this.quickKeys.has(k) ) {
        const { href } = this.quickKeys.get(k)
        window.location.href = href
      }
    }
    
    this.searchElt = document.createElement("div")
    this.searchElt.setAttribute("id","search_div")
    this.searchInput = document.createElement("input")
    this.searchElt.append(this.searchInput)
    this.searchOutput = document.createElement("ul")
    this.searchOutput.classList.add("search_output")
    this.searchElt.append(this.searchOutput)
    this.searchInput.addEventListener("input",e => {
      this.searchBoxInputHandler()
    })
    document.body.append(this.searchElt)
  }
  showVersions() {
    // show in a dialog
    let { local: path } = this.getUriInfo()
    console.log("showVersions",{path})
    this.ajax.versions(path,e => this.didGetVersions(e),e => this.failedToGetVersions(e))
  }
  didGetVersions(e) {
    console.log("Got versions",e)
    let path = e.path.replace(/\.ptmd$/,"")
    let pagename = path.replace(/^.*\//,"")
    this.versionsBox.showVersions(pagename,path,e.versions)
    //this.infoBox.showContent(div)
  }
  failedToGetVersions(e) {
    console.log("Failed to get versions 183",e)
    let div = document.createElement("div")
    let h1 = document.createElement("h1")
    h1.textContent = "Failed to get versions"
    div.append(h1)
    let pre = document.createElement("pre")
    pre.textContent = e.responseText
    div.append(pre)
    this.errorBox.showContent(div)
  }
  unimplemented(e) {
    console.warn("Unimplemented",e)
  }
  toggleOptionsBar(barName) {
    console.log("toggle options",barName)
    let currentOptionsBar = document.body.getAttribute("options-bar")
    if( currentOptionsBar === barName ) {
      document.body.removeAttribute("options-bar")
    } else {
      document.body.setAttribute("options-bar",barName)
    }
  }
  toggleSearchBox() {
    this.searchElt.classList.toggle("visible")
    if( this.searchElt.classList.contains("visible") ) {
      this.searchInput.value = ""
      this.searchOutput.textContent = ""
      this.searchInput.focus()
    }
  }
  searchBoxInputHandler() {
    let v = this.searchInput.value
    let matches = this.findLinksMatching(v)
    if( matches === null ) return
    this.searchOutput.textContent = ""
    for( let m of matches ) {
      let a = document.createElement("a")
      a.href = m.href
      a.textContent = m.textContent
      let li = document.createElement("li")
      li.append(a)
      this.searchOutput.append(li)
    }
  }
  findLinksMatching(x) {
    if( !this.linksInBody ) {
      this.getLinksInBody()
    }
    if( x === "" ) return []
    let r = []
    let re
    x = x.replace(/\s+/g,".*")
    try {
      re = new RegExp(x,"i")
    } catch( e ) {
      console.log("Invalid RegExp",x)
      return null
    }
    for( let a of this.linksInBody ) {
      if( a.textContent.match(re) ) {
        r.push(a)
      }
    }
    return r
  }
  handleEscape() {
    this.searchElt.classList.remove("visible")
  }
  handleEnter() {
    if( this.searchElt.classList.contains("visible") ) {
      if( document.activeElement === this.searchInput ) {
        const out = this.searchOutput
        const links = qq("a",out)
        if( links.length == 0 ) {
          return false
        } else if( links.length == 1 ) {
          const href = links[0].href
          window.location.href = href
          return true
        } else {
          links[0].focus()
          return true
        }
      }
    }
    return false
  }
  setupKeys() {
    super.setupKeys()
    const f = (t,d,h) => this.keys.addfp(t,d,h)
    const fc = (t,d,h) => this.keys.addfpc(t,d,h)
    const n = (t,d,h) => this.keys.addnp(t,d,h)

    n("/","toggle search box", e => this.toggleSearchBox())
    fc("enter","enter handler", e => this.handleEnter())
    f("escape","escape handler", e => this.handleEscape())
    n("C-`","edit page",e => this.editPage())
    n("S-`","edit page",e => this.editPage())
    n("S-c","copy selected pre", e => {
      if( this.selectedPreCode ) {
        this.copyTextFrom(this.selectedPreCode)
      }
    })
    n("A-C-c","copy source", e => {
      let { local: path } = this.getUriInfo()
      this.ajax.source(path,e => {
        let { source } = e
        let clipboard = navigator.clipboard
        if( ! clipboard ) {
          console.warn("No clipboard")
          return
        }
        navigator.clipboard.writeText(source)
        this.infoBox.showContent("Copied source")
      }, e => {
        this.errorBox.showContent("Failed to get source")
      })
    })
    n("S-w","toggle pre wrap", e => {
      document.body.classList.toggle("pre-wrap")
    })
    n("S-arrowup","go to parent dir", e => {
      window.location.href = "../home"
    })
    n("S-h","goto home in current dir",e => {
      window.location.href = "home"
    })
    n("C-/","goto root",e => {
      window.location.href = "/"
    })
    n("S-g","open goto box", e => {
      this.gotoBox.newTab = false
      this.gotoBox.show()
    })
    n("arrowleft","prev link in body", e => this.prevLinkInBody())
    n("arrowright","next link in body", e => this.nextLinkInBody())
    n("S-l","show quick keys", e => this.showQuickKeys())
    n("S-t","toggle touch mode", e => {
      document.body.classList.toggle("touch-mode")
    })
    n("S-v","show versions", e => this.showVersions())
    n("S-d","directory listing of current subdir", e => window.location.href=".dir")
    n("S-r","recent changes in current subdir", e => window.location.href=".recent")
    f("C-d","show hide directory in sidebar", e => this.showSide("dir"))
    f("C-S-h","show hide toc in sidebar", e => this.showSide("toc"))
    f("A-C-S-t","open in typing mode", e => {
      let url = window.location.href
      url = url.split("?")[0]
      url += "?t"
      window.location.href = url
    })
    n("S-n","toggle section numbering", e => this.toggleSectionNumbering())
  }
  toggleSectionNumbering() {
    if( window.numberingDone === true ) {
      document.body.classList.toggle("numbering")
    } else {
      doNumbering()
    }
  }
  showSide(what) {
    let a = document.body.getAttribute("show-side")
    if( a === what ) {
      document.body.setAttribute("show-side","")
      this.currentLinkSet = this.linksInBody
      this.currentLinkIndex = 0
    } else {
      document.body.setAttribute("show-side",what)
      const selector = `div.sidebar[sidebar="${what}"]`
      const sidebar = q(selector)
      if( sidebar ) {
        this.currentLinkSet = qq("a",sidebar)
        this.currentLinkIndex = 0
      }
    }
  }
  showQuickKeys() {
    let div = document.createElement("div")
    let h1 = document.createElement("h1")
    h1.textContent = "Quick Keys"
    div.append(h1)
    let table = document.createElement("table")
    div.append(table)
    table.classList.add("quick-keys")
    let ks = this.quickKeys.keys()
    for( k of ks ) {
      const { name, href } = this.quickKeys.get(k)
      let tr = document.createElement("tr")
      let td, a
      td = document.createElement("td")
      td.classList.add("key")
      td.textContent = k
      tr.append(td)
      td = document.createElement("td")
      td.classList.add("name")
      td.textContent = name
      tr.append(td)
      td = document.createElement("td")
      td.classList.add("href")
      a = document.createElement("a") 
      a.setAttribute("href",href)
      a.textContent = href
      td.append(a)
      tr.append(td)
      table.append(tr)
    }
    this.infoBox.showContent(div,10000)
  }
  getLinksInBody() {
    this.linksInBody = qq("section.main a")
  }
  prevLinkInBody() {
    if( ! this.linksInBody ) {
      this.getLinksInBody()
      this.currentLinkIndex = 0
      let a = document.body.getAttribute("show-side")
      if( a === "" || !a ) {
        this.currentLinkSet = this.linksInBody
      }
    }
    if( !this.currentLinkSet ) {
      console.log("No link set")
      return
    }
    if( this.currentLinkSet.length == 0 ) return console.log("no links in current set")
    const i = this.currentLinkSet.length + this.currentLinkIndex - 1
    this.currentLinkIndex = i % this.currentLinkSet.length
    this.currentLinkSet[this.currentLinkIndex].focus()
  }
  nextLinkInBody() {
    if( ! this.linksInBody ) {
      this.getLinksInBody()
      this.currentLinkIndex = 0
      let a = document.body.getAttribute("show-side")
      console.log({a})
      if( a === "" || !a ) {
        this.currentLinkSet = this.linksInBody
      }
    }
    if( !this.currentLinkSet ) {
      console.log("No link set")
      return
    }
    if( this.currentLinkSet.length == 0 ) return console.log("no links")
    const i = this.currentLinkSet.length + this.currentLinkIndex + 1
    this.currentLinkIndex = i % this.currentLinkSet.length
    this.currentLinkSet[this.currentLinkIndex].focus()
  }
  editPage() {
    let href = this.hereWithAction("edit")
    window.location.href = href
  }
  compileQuickKeys() {
    this.quickKeys = new Map()
    const links = qq("a")
    links.forEach(link => {
      const n = link.nextSibling
      let m
      if( n && ( m = n.textContent.match(/\[([a-z0-9])\]/) ) ) {
        const ntc = n.textContent
        let newTextContent = ntc.substr(3)
        let span = document.createElement("span")
        span.classList.add("quick-key")
        span.textContent = ntc.substr(0,3)
        n.parentNode.insertBefore(span,n)
        n.textContent = newTextContent
        const key = m[1]
        const name = link.textContent
        const href = link.getAttribute("href")
        this.quickKeys.set(key, { name, href })
      }
    })
  }
  copyTextFrom(pre) {
    const text = pre.textContent
    let clipboard = navigator.clipboard
    if( clipboard ) {
      navigator.clipboard.writeText(text)
      this.signalCopy(pre)
    } else {
      // old school fallback
      const textArea = document.createElement("textarea");
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand('copy');
        this.signalCopy(pre)
      } catch (err) {
        console.error('Unable to copy to clipboard', err);
      }
      document.body.removeChild(textArea);
    }
  }
  signalCopy(elt) {
    let summary = elt.textContent
    elt.setAttribute("select-status","copied")
    if( summary.length > 100 ) {
      summary = summary.substr(0,100)+"..."
    }
    this.infoBox.showContent(`Copied: ${summary}`,this.signalCopyTime)
    setTimeout(_ => { 
      elt.removeAttribute("select-status")
    },this.signalCopyTime)
  }
  handleClick(e) {
    let elt = e.target
    this.last = elt
    if( !elt.tagName ) return
    if( this.lastpre ) {
      this.lastpre.classList.remove("jda-selected")
    }
    while( elt.tagName.toLowerCase() !== "BODY" &&
           elt.tagName.toLowerCase() !== "PRE" ) {
      elt = elt.parentElement
      if( ! elt ) return
    }
    if( elt.tagName === "PRE" ) {
      this.lastpre = elt
      this.lastpre.classList.add("jda-selected")
    } else {
      this.lastpre = undefined
    }
  }
  postprocess() {
    qq("img").forEach(elt => {
      elt.addEventListener("click", e => {
        if( e.ctrlKey ) {
          const src = elt.getAttribute("src")
          if( src ) {
            e.preventDefault()
            window.open(src,"_blank")
            return
          }
        }
      })
    })
    qq("pre > code").map(x => x.parentElement).map(x => {
      x.addEventListener("click", e => {
        if( this.selectedPreCode ) { this.selectedPreCode.removeAttribute("select-status") }
        this.selectedPreCode = x
        x.setAttribute("select-status","selected")
      })
      x.addEventListener("dblclick", e => {
        if( e.shiftKey ) { 
          this.copyTextFrom(e.target)
        }
      })
    })
    qq(".verse").forEach(x => x.addEventListener("click", e => {
      if( e.ctrlKey ) {
        e.preventDefault()
        document.body.classList.toggle("wrap")
      }
    }))
  }
  init() {
    super.init()
    this.compileQuickKeys()
    if(hljs) hljs.highlightAll(); else console.warn("no hljs")
    this.postprocess()
  }
}
function doNumbering() {
  if( window.numberingDone === true ) return
  document.body.classList.add("numbering")
  window.numberingDone = true
  let hs = qq("h1,h2,h3,h4,h5,h6",q("section.main"))
  let counters = [0,0,0,0,0,0]
  for(let h of hs) {
    let n = parseInt(h.tagName.substr(1))
    n  -= 1
    counters[n] += 1
    for(let i = n+1; i < counters.length; i++) {
      counters[i] = 0
    }
    let cs = counters.slice(0,n+1)
    let x = cs.join(".")
    let t = h.innerHTML
    let span = document.createElement("span")
    span.classList.add("heading-number")
    span.textContent = x
    h.insertBefore(span,h.firstChild)
  }
}
function handleOptions() {
  for(let key in window.pageOptions) {
    switch(key) {
      case "numbering":
        doNumbering()
        break
    }
  }
}


window.addEventListener("load", _ => {
  const ajax = new Ajax()
  const ui = new PTUIView(ajax)
  function stripActionView() {
    let href = window.location.href
    if( href.endsWith("?action=view") ) {
      let newhref = href.substr(0,href.length-"?action=view".length)
      window.history.replaceState(null,"",newhref)
    }
  }
  stripActionView()
  ui.init()
  handleOptions()
})
