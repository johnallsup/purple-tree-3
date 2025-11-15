//
// exp5 -- a simple minimalist typing practice program
//
// copyright John Allsup 2021
//
// You may use and redistribute this under the terms of the GNU GPL version 3.
//
//
let autostart = true
window.q = (x,y=document) => y.querySelector(x)
window.qq = (x,y=document) => Array.from(y.querySelectorAll(x))
function acms(e) {
    let key = e.key
    const code = e.code.toLowerCase()
    if( code === "backquote" ) { key = "`" }
    if( code.startsWith("digit") ) { key = code.substr(5) }
    if( code === "space" ) { key = "space" }
    let t = key.toLowerCase()
    if( e.shiftKey ) t = "S-"+t
    if( e.metaKey ) t = "M-"+t
    if( e.ctrlKey ) t = "C-"+t
    if( e.altKey ) t = "A-"+t
    return t
}
window.textareaFontSmaller = () => {
  window.textareaSizeFactor /= 1.1
  window.updateTextAreaFontSize()
}
window.textareaFontLarger = () => {
  window.textareaSizeFactor *= 1.1
  window.updateTextAreaFontSize()
}
window.updateTextAreaFontSize = () => {
  let fs = window.textareaBaseFontSize
  m = fs.match(/^([\d\.]+)([a-z]+)$/)
  if( !m ) {
    return console.log("No match",fs)
  }
  let [ _, x, units ] = m
  x = parseFloat(x)
  let y = x * window.textareaSizeFactor
  let z = y + units
  textarea.style.fontSize = z
}
window.textareaSizeFactor = 1.0

const error_audio_cacahe = new Audio('horn.mp3');
let horn = false
function armHorn() {
  horn = true
  document.body.classList.add("horn")
}
function disarmHorn() {
  horn = false
  document.body.classList.remove("horn")
}
function play_error_sound() {
  if( !horn ) return
  disarmHorn()
  const audio = new Audio('horn.mp3');
  audio.play();
}

window.addEventListener("load",() => {
  if( autostart ) {
    startTypingPractice()
  }
})

const getAttribution = () => {
  let attribution
  try {
    attribution = attrib
  } catch(e) {
    attribution = undefined
  }
  return attribution
}

const startTypingPractice = () => {
  const source = document.body.innerHTML
  document.body.innerHTML = "<div class='loading'>Loading...</div>";
  setTimeout(() => {
    document.body.innerHTML = ""
    const container = document.body
    window.typingPractice = new TypingPractice(container, source, getAttribution())
    window.typingPractice.init()
    window.typingPractice.restart()
  },0)
}

const startTypingPracticeWith = (source) => {
  document.body.innerHTML = "<div class='loading'>Loading...</div>";
  setTimeout(() => {
    document.body.innerHTML = ""
    const container = document.body
    const typingPractice = new TypingPractice(container, source, getAttribution())
    typingPractice.init()
  },0)
}

const npad = (x,n) => {
  return `${'0'*n}${x}`.substr(-n)
}
const ndp = (x,n) => {
  const shifted = Math.round(x * 10**n)
  const unshifted = shifted / 10**n
  const intPart = Math.floor(unshifted)
  const fracPart = Math.floor(shifted % 10**n)
  const zeroes = '0'.repeat(n)
  const zeroPadded = `${zeroes}${fracPart}`.substr(-n)
  return `${intPart}.${zeroPadded}`
}
const pad2 = x => npad(x,2)
const onedp = x => ndp(x,1)
const twodp = x => ndp(x,2)
const threedp = x => ndp(x,3)
//const pad2 = x => `00${x}`.substr(-2)

class ErrorPolicyButton {
  constructor() {
    this.dom = document.createElement("span")
    this.dom.classList.add("scoreboard_element","error_policy_button")
    this.button = document.createElement("button")
    this.hornspan = document.createElement("span")
    this.hornspan.classList.add("horn")
    this.hornspan.textContent = "📣";
    this.dom.appendChild(this.button)
    this.dom.append(this.hornspan)
    this.restart = false
    this.button.addEventListener("click", e => {
      e.preventDefault()
      this.toggle()
    })
    this.update()
  }
  toggle() {
    this.restart = ! this.restart
    this.update()
  }
  update() {
    this.button.style.backgroundColor = this.restart ? "#a00" : "#070"
    this.button.innerText = this.restart ? "ErrorRestart" : "ErrorContinue"
  }
}

// tomorrow: use flex box to spread scores out
class ScoreBoard {
  constructor(parent, container) {
    this.dom = document.createElement("div")
    this.dom.classList.add("score_board")
    this.parent = parent
    this.container = container

    this.errorPolicyButton = new ErrorPolicyButton()
    console.log({ep:this.errorPolicyButton,dom:this.errorPolicyButton.dom})

    this.percentage = document.createElement("span")
    this.percentage.classList.add("scoreboard_element","percentage")

    this.wpmSpan = document.createElement("span")
    this.wpmSpan.classList.add("scoreboard_element","wpm")
    this.wpmSpan.innerText = "WPM"

    this.lineNumber = document.createElement("span")
    this.lineNumber.classList.add("scoreboard_element","line_number")

    this.timeElapsed = document.createElement("span")
    this.timeElapsed.classList.add("scoreboard_element","time_elapsed")
    this.timeElapsed.innerText = "00:00"

    this.dom.appendChild(this.errorPolicyButton.dom)
    this.dom.appendChild(this.percentage)
    this.dom.appendChild(this.wpmSpan)
    this.dom.appendChild(this.lineNumber)
    this.dom.appendChild(this.timeElapsed)

    container.appendChild(this.dom)
  }
  i = 0
  startTime = null
  lastTickTime = null
  lastKeysCorrect = null
  timerPeriod = 1000
  wpms = []
  startTimer() {
    if( this.interval ) clearInterval(this.interval)
    this.lastKeysCorrect = 0
    const currentTime = new Date().getTime()
    this.lastTickTime = currentTime
    this.parent.keyPressesCorrect = 0
    this.parent.keyPressesTotal = 0
    this.parent.keyPressesCorrect = 0
    this.parent.keyPressesErrors = 0
    this.lastKeysCorrect = 0
    this.wpms = []
    this.maxwpm = 0
    this.startTime = this.lastTickTime = new Date().getTime()
    this.interval = setInterval(this.tick.bind(this),this.timerPeriod)
    this.tick.bind(this)()
    this.timeElapsed.innerText = "00:00"
  }
  tick() {
    const { keyPressesCorrect } = this.parent
    const currentTime = new Date().getTime()
    const dt = currentTime - this.lastTickTime
    if( dt === 0 ) {
      console.log("dt=0")
      return
    }
    const Dt = currentTime - this.startTime
    this.lastTickTime = currentTime

    // compute wpm -- instantaneous, avg over a minute, and avg overall
    const dk = keyPressesCorrect - this.lastKeysCorrect
    this.lastKeysCorrect = keyPressesCorrect
    const wpmi = 60*1000*dk/(5*dt)
    const wpm = 60*1000*keyPressesCorrect/(5*Dt)
    this.maxwpm = Math.max(wpm,this.maxwpm)
    this.wpms.push(wpmi)
    if( this.wpms.length > 60 ) {
      this.wpms = this.wpms.slice(this.wpms.length-60)
    }
    const wpm_sum = this.wpms.reduce((x,y)=>x+y,0)
    const avg_wpm = wpm_sum/this.wpms.length
    this.wpmSpan.innerText = `WPM [ ${onedp(wpmi)} ${onedp(avg_wpm)} ${onedp(wpm)} ] max=${onedp(this.maxwpm)}`

    // elapsed time
    const secs = Math.floor(Dt/1000)
    const mins = Math.floor(secs/60)
    const hours = Math.floor(mins/50)
    let t = `${pad2(mins)}:${pad2(secs)}`
    if( hours > 0 ) {
      t = `${hours}:${t}`
    }
    this.timeElapsed.innerText = t
  }
  update() {
    const { keyPressesTotal, keyPressesCorrect, keyPressesErrors } = this.parent
    if( keyPressesTotal === 0 ) {
      this.percentage.innerText = "Start"
    } else {
      const errors = keyPressesTotal - keyPressesCorrect
      const errorRate = keyPressesTotal / errors
      const errorRateStr = onedp(errorRate)
      const errorRateStr2 = errors ? `${errorRateStr} keys/error` : `no errors`
      this.percentage.innerText = `Accuracy: ${keyPressesCorrect}/${keyPressesTotal} = ${threedp(100*keyPressesCorrect/keyPressesTotal)}% (${keyPressesTotal-keyPressesCorrect} errors, ${errorRateStr2})`
    }

    const textArea = this.parent.textArea
    const numLines = textArea.lines.length
    const row = textArea.row + 1
    this.lineNumber.innerText = `Line: ${row}/${numLines}`
  }
}

class TextLine {
  // manages an individual line
  constructor(parent, container, source, rownum) {
    this.source = source.replace(/\s+$/,"") // remove trailing whitespace
    this.container = container
    this.parent = parent
    this.errorCount = 0
    this.wrongAttempts = 0
    this.totalErrors = 0
    this.dom = document.createElement("div")
    this.dom.classList.add("text_line")
    this.left = document.createElement("span")
    this.right = document.createElement("span")
    this.mid = document.createElement("span")
    this.errors = document.createElement("span")
    this.left.classList.add("left")
    this.right.classList.add("right")
    this.mid.classList.add("mid")
    this.errors.classList.add("errors")
    this.dom.appendChild(this.left)
    this.dom.appendChild(this.mid)
    this.dom.appendChild(this.right)
    this.dom.appendChild(this.errors)
    this.rownum = rownum
    this.container.appendChild(this.dom)
  }
  incErrorCount() {
    this.dom.classList.add("errors")
    this.errorCount++
    this.totalErrors++
    this.updateErrors()
  }
  clearErrors() {
    this.errorCount = 0
    this.dom.classList.remove("errors")
    this.updateErrors()
  }
  clearTotalErrors() {
    this.totalErrors = 0
    this.clearErrors()
  }
  restartLine() {
    if( this.errorCount > 0 ) {
      this.wrongAttempts++
    }
    this.clearErrors()
  }
  updateErrors() {
    if( this.totalErrors > 0 ) {
      this.errors.classList.add("non-zero-errors")
      if( this.wrongAttempts > 0 ) {
        this.errors.innerHTML = `<span class='linestats'><span class='current-errors'>errors=${this.errorCount}</span>, <span class='total-errors'>total=${this.totalErrors}</span>, <span class='wrong-attempts'>wrong attempts=${this.wrongAttempts}</span></span>`
      } else {
        this.errors.innerHTML = `<span class='linestats'><span class='current-errors'>errors=${this.errorCount}</span></span>`
      }
    } else {
      this.errors.classList.remove("non-zero-errors")
      this.errors.innerHTML = ""
    }
  }
  update(row,col) {
    this.dom.classList.remove("before")
    this.dom.classList.remove("current")
    this.dom.classList.remove("after")
    if( this.rownum < row ) {
      // line is before current
      this.left.innerText = this.source
      this.mid.innerText = ""
      this.right.innerText = ""
      this.dom.classList.add("before")
    } else if( this.rownum > row ) {
      // line is after current
      this.left.innerText = this.source
      this.mid.innerText = ""
      this.right.innerText = ""
      this.dom.classList.add("after")
    } else {
      // line is current -- split into left mid right
      this.left.innerText = this.source.slice(0,col)
      this.mid.innerText = this.source[col]
      this.right.innerText = this.source.slice(col+1)
      this.dom.classList.add("current")
    }
  }
  charAt(col) {
    return this.source[col]
  }
  get length() {
    return this.source.length
  }
}

class TextArea {
  row = 0
  col = 0
  constructor(parent, container, source) {
    this.parent = parent
    this.container = container
    this.source = source // don't reallyneed
    this.lastKeys = parent.lastKeys

    this.dom = document.createElement("div")
    this.dom.classList.add("textarea")
    this.container.appendChild(this.dom)
    window.textarea = this.dom
    setTimeout(_ => {
      let computedStyle = window.getComputedStyle(window.textarea)
      window.textareaBaseFontSize = computedStyle.getPropertyValue("font-size")
      window.updateTextAreaFontSize()
    },0)

    const source_lines = source.split("\n")
    this.lines = source_lines.map((line,i) => new TextLine(this,this.dom,line,i))
    
    this.setPos(0,0)
  }
  clearErrors() {
    this.lines.forEach(line => {
      line.clearErrors()
    })
  }
  clearTotalErrors() {
    this.lines.forEach(line => {
      line.clearTotalErrors()
    })
  }
  testKey(key) {
    const expected = this.currentLine.charAt(this.col)
    const { row, col } = this
    if( expected != " " ) { // allow extra spaces at the start of the line (due to auto newline)
      if( key == " " && col == 0 ) {
        this.parent.signalAllowedSpace()
        return
      }
    }
    this.lastKeys.append(key,expected)
    if( key === expected ) {
      this.parent.signalCorrect()
      this.advanceChar()
    } else {
      this.parent.actualCharDiv.textContent = key
      this.parent.expectedCharDiv.textContent = expected
      this.parent.signalError()
      this.error()
    }
  }
  setPos(row,col) {
    this.row = (row+this.lines.length)%this.lines.length
    this.currentLine = this.lines[this.row]
    const p = this.parent
    const repc = p.repCount
    if( repc > 1 ) {
      if( ( this.row % repc ) === 0 ) {
        document.body.classList.add("firstrep")
      } else {
        document.body.classList.remove("firstrep")
      }
    } else {
      document.body.classList.remove("firstrep")
    }
    const l = this.currentLine.length
    if( col < 0 ) {
      this.col = 0
    } else if( col >= l ) {
      this.col = l-1
    } else {
      this.col = col
    }
    this.lines.forEach(line => line.update(this.row,this.col))
    this.updateLines()
    this.currentLine.dom.scrollIntoView({
      block: "center"
    })
  }
  advanceChar() {
    const l = this.currentLine.length
    this.col++
    if( this.col >= this.currentLine.length ) {
      // ugly! -- this is why we do want a global app state
      console.log(42,this.parent.scoreBoard.errorPolicyButton.restart,this.currentLine.errorCount)
      if( this.parent.scoreBoard.errorPolicyButton.restart && this.currentLine.errorCount > 0 ) {
        return this.restartCurrentLine()
      } else {
        return this.advanceLine()
      }
    }
    this.updateLines()
  }
  advanceLine() {
    this.setPos(this.row+1, 0)
    this.updateLines()
  }
  updateLines() {
    this.lines.forEach(line => line.update(this.row,this.col))
  }
  error() {

  }
  restartCurrentLine() {
    this.currentLine.restartLine()
    this.moveToStartOfLine()
  }
  moveToStartOfLine() {
    this.setPos(this.row,0)
    this.updateLines()
  }
  moveUp() {
    this.setPos(this.row-1,this.col)
    this.updateLines()
  }
  moveDown() {
    this.setPos(this.row+1,this.col)
    this.updateLines()
  }
  pageUp() {
    const newrow = Math.max(this.row - 20, 0)
    this.setPos(newrow,this.col)
    this.updateLines()
  }
  pageDown() {
    const newrow = Math.min(this.row + 20, this.lines.length-1)
    this.setPos(newrow,this.col)
    this.updateLines()
  }
  moveRight() {
    this.setPos(this.row,this.col+1)
    this.updateLines()
  }
  moveLeft() {
    this.setPos(this.row,this.col-1)
    this.updateLines()
  }
  skipWhitespace() {
    while(this.currentLine.charAt(this.col) === " ") {
      this.advanceChar()
    }
  }
}

class LastKeys {
  // goes in the footer, shows previous keypresses, coloured according to correct/wrong
  lastKeyCount = 80
  lastKeys = []
  constructor(parent, container) { // parent is 
    this.dom = document.createElement("div")
    this.dom.classList.add("last_keys")
    this.parent = parent
    this.container = container
    container.appendChild(this.dom)
    for(let x of "Last Keys:") {
      this.append(x,x)
    }
  }
  append(key,expected) {
    this.lastKeys.push({
      key, expected
    })
    if( this.lastKeys.length > this.lastKeyCount ) {
      this.lastKeys = this.lastKeys.slice(this.lastKeys.length - this.lastKeyCount)
    }
  }
  update() {
    const spans = this.lastKeys.map(this.makeSpan)
    this.dom.innerHTML = ""
    spans.forEach(span => this.dom.appendChild(span))
  }
  makeSpan({key,expected}) {
    if( key === expected ) {
      const span = document.createElement("span")
      span.innerText = key
      span.classList.add('correct')
      return span
    } else {
      const span = document.createElement("span")
      const span_key = document.createElement("span")
      const span_exp = document.createElement("span")
      span_key.textContent = key
      span_exp.textContent = expected
      span_key.classList.add('wrong')
      span_exp.classList.add('expected')
      span.appendChild(span_key)
      span.appendChild(span_exp)
      return span
    }
    /*
    const span = document.createElement("span")
    span.innerText = key
    span.classList.add(key === expected ? 'correct' : 'wrong' )
    return span
    */
  }
}

const escapeText = text => text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
const unescapeText = text => text.replace(/&gt;/g,">").replace(/&lt;/g,"<").replace(/&amp;/g,"&")

// regex from: https://stackoverflow.com/questions/3809401/what-is-a-good-regular-expression-to-match-a-url
const isUrl = text => text.match(/^https?:\/\//)

class TypingPractice {
  started = false
  constructor(container, source, attribution) {
    this.container = container
    this.source = this.processSource(source)
    if( attribution ) {
      this.attribution = attribution.trim()
    }
    if( this.source.length === 0 ) {
      this.source = "Source is empty!"
      throw "Source is empty!"
    }
    this.repCount = 1
    const searchParams = new URLSearchParams(window.location.search)
    if(searchParams.has('count')) {
      let count = searchParams.get("count")
      if( count.match(/^\d+$/) ) {
        count = parseInt(count)
        if( count > 10 ) { count = 10; }
        if( count < 1 ) { count = 1; }
        this.repCount = count  
      }
    }
    this.practiceMode = "normal"
  }
  createDom() {
    this.dom = document.createElement("div")
    this.dom.classList.add("typing_app")
    this.typingInput = document.createElement("input")
    this.typingInput.classList.add("typing_input")
    
    this.header = document.createElement("div")
    this.header.classList.add("ui_area","header")
    this.footer = document.createElement("div")
    this.footer.classList.add("ui_area","footer")
    this.mainArea = document.createElement("div")
    this.mainArea.classList.add("ui_area","main")
    this.dom.appendChild(this.header)
    this.dom.appendChild(this.mainArea)
    this.dom.appendChild(this.footer)

    this.scoreBoard = new ScoreBoard(this,this.header)
    this.lastKeys = new LastKeys(this,this.footer)
    if( this.attribution ) {
      this.attributionDom = document.createElement("p")
      this.attributionDom.classList.add("attribution")
      if( isUrl(this.attribution) ) {
        this.attributionDom.innerHTML = `From: <a href="${this.attribution}">${this.attribution}</a>`
      } else {
        this.attributionDom.innerText = `From: ${this.attribution}`
      }
      this.mainArea.appendChild(this.attributionDom)
    }
    this.textArea = new TextArea(this,this.mainArea,this.source)
    this.scoreBoard.update()
    this.lastKeys.update()

    this.container.appendChild(this.dom)
    this.container.append(this.typingInput)
    this.mainAreaCapsOffColour = this.mainArea.style.backgroundColor
    this.mainAreaCapsOnColour = "yellow"
    this.mainAreaNormalColour = this.mainAreaCapsOffColour
    this.mainAreaErrorColour = "red"

    this.errorDialog = document.createElement("dialog")
    this.errorDialog.classList.add("error-dialog")
    let div, div2
    
    div2 = document.createElement("div")
    div = document.createElement("div")
    div.classList.add("label")
    div.textContent = "Expected"
    div2.append(div)
    this.expectedCharDiv = document.createElement("div")
    this.expectedCharDiv.classList.add("expected-key")
    this.expectedCharDiv.innerHTML = "&nbsp;"
    div2.append(this.expectedCharDiv)
    this.errorDialog.append(div2)

    div2 = document.createElement("div")
    div = document.createElement("div")
    div.classList.add("label")
    div.textContent = "Actual"
    div2.append(div)
    this.actualCharDiv = document.createElement("div")
    this.actualCharDiv.classList.add("actual-key")
    this.actualCharDiv.innerHTML = "&nbsp;"
    div2.append(this.actualCharDiv)
    this.errorDialog.append(div2)
    document.body.append(this.errorDialog)
    window.errd = this.errorDialog
    this.typingInput.focus()
  }
  errorDialogTime = 5000
  flashTimeout = 100
  keyPressesTotal = 0
  keyPressesCorrect = 0
  keyPressesErrors = 0
  capsOn() {
    this.mainAreaNormalColour = this.mainAreaCapsOnColour
    this.mainArea.style.backgroundColor = this.mainAreaCapsOnColour
  }
  capsOff() {
    this.mainAreaNormalColour = this.mainAreaCapsOffColour
    this.mainArea.style.backgroundColor = this.mainAreaCapsOffColour
  }
  flashBackround(color) {
    this.mainArea.style.backgroundColor = color
    setTimeout(_ => this.mainArea.style.backgroundColor = this.mainAreaNormalColour, this.flashTimeout)
  }
  signalAllowedSpace() {
    this.flashBackround("green")
  }
  signalCorrect() {
    this.keyPressesTotal++
    this.keyPressesCorrect++
    this.scoreBoard.update()
  }
  signalError() {
    this.keyPressesTotal++
    this.keyPressesErrors++
    this.textArea.currentLine.incErrorCount()
    this.scoreBoard.update()
    this.flashBackround("red")
    this.errorDialog.show()
    console.log(this.errorDialog)
    if( this.errorDialogTimeout ) clearTimeout(this.errorDialogTimeout)
    this.errorDialogTimeout = setTimeout(_ => this.errorDialog.close(),this.errorDialogTime)
    play_error_sound() 
  }
  processSource(source) {
    const trimmed = source.trim()
    const unescaped = unescapeText(trimmed)
      .replace(/[\u201c\u201d]/g,'"') // curly double quotes
      .replace(/[\u2018\u2019]/g,"'") // curly single quotes
      .replace(/\u2014/g,"---") // m dash
      .replace(/\u2013/g,"-") // n dash
      .replace(/\xa0/g," ") // nonbreaking space
    const tabsExpanded = unescaped.replace(/\t/g,"    ")
    const split = tabsExpanded.split("\n")
    const filtered = split.filter(x => ! x.match(/^\s*$/))
    const trimmed_lines = filtered.map(x => x.replace(/\s+$/,""))
    const joined = trimmed_lines.join("\n")
    return joined
  }
  init() {
    // setup the window
    this.dom = this.createDom()
    window.addEventListener("blur",this.handleBlur)
    window.addEventListener("focus",this.handleFocus)
    window.focus()
    this.start()
    if( window.baseFontSize ) {
      window.textareaSizeFactor = window.baseFontSize
    }
  }
  handleBlur(e) {
    const dom = document.querySelector(".typing_app")
    dom.classList.remove("focus")
    dom.classList.add("blur")
  }
  handleFocus(e) {
    const dom = document.querySelector(".typing_app")
    dom.classList.remove("blur")
    dom.classList.add("focus")
  }
  start() {
    // start responding to keypresses
    window.addEventListener("keydown",e => this.handleKey(e))
    window.addEventListener("compositionend",e => this.handleComposeEnd(e))
    //this.typingInput.addEventListener("input",e => this.handleInput(e))
    window.setInterval(_ => this.typingInput.focus(),50)
  }
  makeRandomWords(source) {
    let xs = source.trim().split(/\s+/)
    let lines = []
    let nlines = 40
    let linelength = 60
    function choice(xs) {
      return xs[Math.floor(Math.random()*xs.length)]
    }
    for( let i = 0; i < nlines; i++ ) {
      let line = []
      let l = 0
      while( l < linelength ) {
        let x = choice(xs)
        line.push(x)
        l += x.length + 1
      }
      lines.push(line.join(" "))
    }
    return lines.join("\n")
  }
  makeRandomChars(source) {
    source = source.replace(/\s/g,"")
    let chars = Array.from(new Set(source))
    let nlines = 40
    let blocksize = 4
    let nblocks = 8
    function choice(xs) {
      return xs[Math.floor(Math.random()*xs.length)]
    }
    let lines = []
    for( let i = 0; i < nlines; i++ ) {
      let line = []
      for( let j = 0; j < nblocks; j++ ) {
        let block = []
        for( let k = 0; k < blocksize; k++ ) {
          block.push(choice(chars))
        }
        line.push(block.join(""))
      }
      lines.push(line.join(" "))
    }
    return lines.join("\n")
  }
  restart() {
    let source = this.source
    switch( this.practiceMode ) {
      case "random words":
        source = this.makeRandomWords(source)
        break;
      case "random chars":
        source = this.makeRandomChars(source)
        break;
      case "normal":
      default:
        source = source
    }
    let lines = source.split(/\n/)
    let lines2 = []
    lines.forEach(line => {
      for(let j=0; j<this.repCount; j++) {
        lines2.push(line)
      }
    })
    source = lines2.join("\n")
    this.textArea.dom.remove()
    this.textArea = new TextArea(this,this.mainArea,source)
    this.textArea.clearTotalErrors()
    this.textArea.setPos(0,0)
  }
  handleComposeEnd(e) {
    const t = e.data
    const l = t.length
    for( let i=0; i<t.length; i++ ) {
      this.textArea.testKey(t[i])
    }
    console.log("compose end",e,t,l)
    this.lastKeys.update()
    this.scoreBoard.update()
    this.typingInput.value = ""
  }
  handleKey(e) {
    console.log("key",e.key)
    this.typingInput.focus()
    if( e.key === "Process" ) {
      console.log("process, return")
      return
    }
    const capsLockState = e.getModifierState("CapsLock")
 
    // change background colour to indicate caps lock state
    // not perfect since if we toggle caps lock in another window
    // and switch back, it will not be correct until the next
    // keypress
    if( capsLockState ) {
      this.capsOn()
    } else {
      this.capsOff()
    }

    // Alt+r resets timers and error counts
    // Alt+o toggles error policy
    // Most alt-key combos can't be captured this way
    function changePage(newPage) {
      let url = window.location.href
      let cs = url.split("?")
      let a = cs[0]
      let ds = a.split("/")
      ds[ds.length-1] = newPage
      a = ds.join("/")
      cs[0] = a
      let newurl = cs.join("?")
      return newurl
    }
    if( e.altKey ) {
      let m
      const k = acms(e)
      let url = window.location.href
      switch(k) {
        case "A-C-r":
          // restart with random words
          e.preventDefault()
          this.practiceMode = "random words"
          return this.restart()
          return;
        case "A-C-n":
          // restart with normal
          e.preventDefault()
          this.practiceMode = "normal"
          return this.restart()
        case "A-C-k":
          // restart with random chars
          e.preventDefault()
          this.practiceMode = "random chars"
          return this.restart()
        case "A-C-x":
          e.preventDefault()
          {
            const url = window.location.href
            const stem = url.split(/\?/)[0]
            window.location.href = stem
          }
          //window.location.href = "index.php"
          return;
        case "A-S-r":
          this.textArea.clearTotalErrors()
        case "A-r":
          e.preventDefault()
          this.scoreBoard.startTimer()
          this.scoreBoard.update()
          this.textArea.clearErrors()
          return;
        case "A-o":
          console.log("toggle error policy")
          this.scoreBoard.errorPolicyButton.toggle()
          return;
        case "A-[":
          window.textareaFontSmaller()
          return;
        case "A-]":
          window.textareaFontLarger()
          return;
        case "A-h":
          armHorn()
          return;
      }
      if( m = k.match(/A-(\d)/) ) {
        e.preventDefault()
        let n = parseInt(m[1])
        if( n == 0 ) {
          n = 10
        }
        // restart with n repetitions
        this.repCount = n
        return this.restart()
      }
      console.log(k)
    }

    // Otherwise ignore all keypresses that have ctrl or alt helt
    if( e.altKey || e.ctrlKey ) return

    // ignore F-keys too
    if( e.key.match(/F\d/) ) {
      console.log(`F key: ${e.key}`)
      return
    }

    // all other keys get handled here
    e.preventDefault()

    const key = e.key

    if( key === "ArrowLeft" ) {
      this.textArea.moveLeft()
    } else if( key === "ArrowRight" ) {
      this.textArea.moveRight()
    } else if( key === "ArrowUp" ) {
      this.textArea.moveUp()
    } else if( key === "ArrowDown" ) {
      this.textArea.moveDown()
    } else if( key === "PageUp" ) {
      this.textArea.pageUp()
    } else if( key === "PageDown" ) {
      this.textArea.pageDown()
    } else if( key === "Escape" ) {
      this.textArea.restartCurrentLine()
    } else if( key === "Enter" ) {
      this.textArea.advanceLine()
    } else if( key === "Tab" ) {
      this.textArea.skipWhitespace()
    } else if( key.length === 1 ) { // a single char key
      if( !this.started ) {
        this.started = true
        this.scoreBoard.startTimer()
      }
      this.textArea.testKey(key)
    } else {
      // something like shift or control -- ignore
      console.log(key)
    }
    this.lastKeys.update()
    this.scoreBoard.update()
    this.typingInput.value = ""
  }
}
