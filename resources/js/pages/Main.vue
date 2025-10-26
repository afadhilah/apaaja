<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  initialChats: Array
})

const chats = reactive(props.initialChats)
const currentChatIndex = ref(0)
const composer = ref('')
const lastAttachment = ref('')
const copied = ref(false)
const bubbleType = ref(null)
const activeCitation = ref(null)
const isLoading = ref(false)

// Filter states
const topicKeyword = ref('')
const minCitations = ref(3)
const yearFrom = ref(2015)
const yearTo = ref(2025)

const currentChat = computed(() => chats[currentChatIndex.value])

// Collect all unique references from all AI messages in current chat
const allReferences = computed(() => {
  const refs = []
  const seen = new Set()
  
  currentChat.value.messages.forEach(msg => {
    if (msg.type === 'ai' && msg.references && msg.references.length > 0) {
      msg.references.forEach(ref => {
        // Use title as unique identifier
        if (!seen.has(ref.title)) {
          seen.add(ref.title)
          refs.push(ref)
        }
      })
    }
  })
  
  return refs
})


async function pushComposer() {
  if (!composer.value.trim() || isLoading.value) return
  
  const userMessage = composer.value.trim()
  currentChat.value.messages.push({ type: 'user', text: userMessage })
  composer.value = ''
  isLoading.value = true

  // Add loading indicator
  const loadingMessageIndex = currentChat.value.messages.length
  currentChat.value.messages.push({ type: 'ai', text: '⏳ Thinking...' })

  try {
    const response = await axios.post('/chat/send', {
      message: userMessage,
      filters: {
        topic: topicKeyword.value,
        minCitations: minCitations.value,
        yearFrom: yearFrom.value,
        yearTo: yearTo.value
      }
    })

    if (response.data.success) {
      // Replace loading message with actual response + store references with message
      currentChat.value.messages[loadingMessageIndex] = {
        type: 'ai',
        text: response.data.message,
        references: response.data.references || [] // Store references per message
      }

      // Update chat name and last message
      currentChat.value.last = userMessage.substring(0, 50) + '...'
    } else {
      currentChat.value.messages[loadingMessageIndex] = {
        type: 'ai',
        text: '❌ Error: ' + response.data.message
      }
    }
  } catch (error) {
    currentChat.value.messages[loadingMessageIndex] = {
      type: 'ai',
      text: '❌ Error: ' + (error.response?.data?.message || error.message || 'Failed to connect to AI')
    }
  } finally {
    isLoading.value = false
  }
}

function createChat() {
  chats.push({ name: `Chat ${chats.length + 1}`, last: 'New chat', messages: [], references: [] })
  currentChatIndex.value = chats.length - 1
}

function triggerFilePicker() {
  document.getElementById('fileInput').click()
}

function attachFile(e) {
  const f = e.target.files && e.target.files[0]
  if (!f) return
  currentChat.value.messages.push({ type: 'user', text: `📎 ${f.name}` })
  lastAttachment.value = f.name
  e.target.value = ''
  setTimeout(() => { lastAttachment.value = '' }, 4000)
}

function openBubble(type, payload = null) {
  bubbleType.value = type
  copied.value = false
  if (type === 'citation' && payload) {
    activeCitation.value = payload
  } else {
    activeCitation.value = null
  }
}

function closeBubble() {
  bubbleType.value = null
  activeCitation.value = null
  copied.value = false
}

function openPaper(paper) {
  // Try to open paper via DOI, or Semantic Scholar, or Google Scholar
  let url = null
  
  if (paper.doi) {
    // DOI link is most reliable
    url = `https://doi.org/${paper.doi}`
  } else if (paper.paperId) {
    // Semantic Scholar link
    url = `https://www.semanticscholar.org/paper/${paper.paperId}`
  } else if (paper.title) {
    // Fallback to Google Scholar search
    url = `https://scholar.google.com/scholar?q=${encodeURIComponent(paper.title)}`
  }
  
  if (url) {
    window.open(url, '_blank')
  }
}

function copyCitation(format = 'apa') {
  if (!activeCitation.value) return
  const c = activeCitation.value
  
  let citationText = ''
  
  if (format === 'apa') {
    // APA Format
    citationText = `${c.authors} (${c.year}). ${c.title}. ${c.venue || 'Conference Proceedings'}. ${c.doi ? 'https://doi.org/' + c.doi : ''}`
  } else if (format === 'ieee') {
    // IEEE Format
    citationText = `${c.authors}, "${c.title}," ${c.venue || 'Conference Proceedings'}, ${c.year}. ${c.doi ? 'doi: ' + c.doi : ''}`
  } else if (format === 'bibtex') {
    // BibTeX Format
    const key = c.title.split(' ').slice(0, 2).join('').toLowerCase() + c.year
    citationText = `@article{${key},
  title={${c.title}},
  author={${c.authors}},
  year={${c.year}},
  ${c.venue ? `booktitle={${c.venue}},` : ''}
  ${c.doi ? `doi={${c.doi}}` : ''}
}`
  }
  
  navigator.clipboard?.writeText(citationText).then(() => {
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  })
}

function applyFilters() {
  // Filter functionality can be triggered here
  console.log('Filters applied:', {
    topic: topicKeyword.value,
    minCitations: minCitations.value,
    yearRange: `${yearFrom.value}-${yearTo.value}`
  })
}

function formatAIResponse(text) {
  // Replace **text** with <strong>text</strong> for semi-bold
  let formatted = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  
  // Replace [number] with superscript citation links
  formatted = formatted.replace(/\[(\d+)\]/g, (match, num) => {
    return `<sup class="citation-link" data-citation="${num}">[${num}]</sup>`
  })
  
  // Convert markdown lists to HTML lists
  // Match lines that start with * or - followed by a space
  const lines = formatted.split('\n')
  let inList = false
  let result = []
  
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim()
    
    // Check if this line is a list item
    if (line.match(/^[\*\-]\s+(.+)$/)) {
      const content = line.replace(/^[\*\-]\s+/, '')
      
      // Start list if not already in one
      if (!inList) {
        result.push('<ul>')
        inList = true
      }
      
      result.push(`<li>${content}</li>`)
    } else {
      // Close list if we were in one
      if (inList) {
        result.push('</ul>')
        inList = false
      }
      
      result.push(line)
    }
  }
  
  // Close list if still open at end
  if (inList) {
    result.push('</ul>')
  }
  
  formatted = result.join('\n')
  
  // Replace line breaks
  formatted = formatted.replace(/\n/g, '<br>')
  
  return formatted
}

function handleCitationClick(event) {
  // Check if clicked element is a citation link
  const citationLink = event.target.closest('.citation-link')
  if (!citationLink) return
  
  const citationNum = citationLink.dataset.citation
  
  // Find the message element that contains this citation
  const messageElement = event.target.closest('.message-bubble')
  if (!messageElement) {
    console.warn('Could not find parent message element')
    return
  }
  
  // Get message index from data attribute
  const messageIndex = messageElement.dataset.messageIndex
  if (messageIndex === undefined) {
    console.warn('Message index not found')
    return
  }
  
  const message = currentChat.value.messages[parseInt(messageIndex)]
  if (!message || !message.references || message.references.length === 0) {
    console.warn('No references found for this message')
    return
  }
  
  const index = parseInt(citationNum) - 1
  if (index >= 0 && index < message.references.length) {
    const citation = message.references[index]
    openBubble('citation', citation)
  } else {
    console.warn(`Citation [${citationNum}] not found in message references`)
  }
}

</script>

<template>
  <Head title="CompBuddy — Main" />
  
  <div class="flex flex-col h-screen text-white cs-wallpaper">
    <!-- Header -->
    <header class="flex items-center justify-between px-6 py-3 border-b panel border-panel">
      <div class="flex items-center gap-4">
        <!-- <img src="" alt="Logo" class="w-20 h-30 object-contain shadow-md" /> -->
        <div class="text-xl font-bold">CompBuddy</div>
        <div class="hidden sm:block text-sm text-gray-400">
          Chatbot for researchers to find & understand CS papers
        </div>
      </div>

      <div class="flex items-center gap-4 text-sm">
        <button @click="openBubble('about')" class="text-gray-300 hover:text-white">about us</button>
        <a href="/" class="bg-blue-600 px-4 py-1 rounded-full">login / sign up</a>
      </div>
    </header>

    <!-- Layout -->
    <div class="flex flex-1 min-h-0">
      <!-- Sidebar -->
      <aside class="w-[20%] panel border-r border-panel flex flex-col">
        <div class="p-4 flex flex-col gap-3">
          <button @click="createChat" class="py-2 bg-blue-600 hover:bg-blue-500 rounded-md text-sm">New chat</button>
          <button @click="openBubble('help')" class="py-2 bg-gray-800 hover:bg-gray-700 rounded-md text-sm">Help</button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
          <div v-for="(c, i) in chats" :key="i" @click="currentChatIndex = i"
            :class="['cursor-pointer p-3 rounded-md', currentChatIndex === i ? 'bg-blue-900/30 text-white' : 'text-gray-300']">
            <div class="font-medium text-sm">{{ c.name }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ c.last }}</div>
          </div>
        </div>

        <div class="text-xs text-gray-500 text-center p-3 border-t border-panel">© CompBuddy</div>
      </aside>

      <!-- Chat Panel -->
      <main class="w-[70%] flex flex-col relative">
        
        <!-- Filter Panel -->
        <div class="px-6 py-3 border-b bg-[#0b1220] border-[#1a2332]">
          <div class="flex items-center gap-3">
            <!-- Topic/Keyword -->
            <div class="flex-1">
              <label class="text-xs text-gray-500 block mb-1">Topic / Keyword</label>
              <input 
                v-model="topicKeyword" 
                type="text" 
                placeholder="e.g. NLP, Deep Learning"
                class="w-full px-3 py-2 bg-[#0f1419] border border-[#1e2a3a] rounded-md text-sm text-gray-200 placeholder-gray-600 focus:border-[#2e4a6a] focus:outline-none"
              />
            </div>

            <!-- Citations -->
            <div class="w-32">
              <label class="text-xs text-gray-500 block mb-1">Citations</label>
              <select 
                v-model="minCitations"
                class="w-full px-3 py-2 bg-[#0f1419] border border-[#1e2a3a] rounded-md text-sm text-gray-200 focus:border-[#2e4a6a] focus:outline-none cursor-pointer"
              >
                <option :value="3">3</option>
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>

            <!-- Year From -->
            <div class="w-24">
              <label class="text-xs text-gray-500 block mb-1">From</label>
              <input 
                v-model.number="yearFrom" 
                type="number" 
                min="1900" 
                max="2025"
                class="w-full px-3 py-2 bg-[#0f1419] border border-[#1e2a3a] rounded-md text-sm text-gray-200 focus:border-[#2e4a6a] focus:outline-none"
              />
            </div>

            <!-- Year To -->
            <div class="w-24">
              <label class="text-xs text-gray-500 block mb-1">To</label>
              <input 
                v-model.number="yearTo" 
                type="number" 
                min="1900" 
                max="2025"
                class="w-full px-3 py-2 bg-[#0f1419] border border-[#1e2a3a] rounded-md text-sm text-gray-200 focus:border-[#2e4a6a] focus:outline-none"
              />
            </div>

            <!-- Apply Filter Button -->
            <div class="pt-5">
              <button 
                @click="applyFilters"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition flex items-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Apply Filter
              </button>
            </div>
          </div>
        </div>
        <div class="flex-1 overflow-y-auto px-8 py-6 space-y-6 relative">
          <div v-for="(m, i) in currentChat.messages" :key="i">
            <div v-if="m.type === 'ai'" class="flex justify-start">
              <div 
                class="bubble-ai message-bubble rounded-xl px-4 py-2 shadow-md max-w-[75%] formatted-content"
                :data-message-index="i"
                v-html="formatAIResponse(m.text)"
                @click="handleCitationClick"
              ></div>
            </div>
            <div v-else class="flex justify-end">
              <div class="bubble-user rounded-xl px-4 py-2 shadow-md max-w-[75%]">
                {{ m.text }}
              </div>
            </div>
          </div>
        </div>

        <!-- Composer -->
        <div class="panel border-t border-panel p-4">
          <div class="flex gap-3 items-center">
            <div>
              <button class="attach-btn" @click="triggerFilePicker" title="Attach file">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.44 11.05l-9.19 9.2a6.5 6.5 0 01-9.2-9.2l9.2-9.19a4.75 4.75 0 016.72 6.72l-9.2 9.19a2.5 2.5 0 01-3.54-3.54l8.01-8.01" />
                </svg>
              </button>
              <input id="fileInput" type="file" class="hidden" @change="attachFile" />
            </div>

            <input v-model="composer" @keyup.enter="pushComposer" placeholder="Type a message..."
              :disabled="isLoading"
              class="flex-1 rounded-md px-4 py-2 bg-[#0b0d10] border border-gray-700 focus:ring-2 focus:ring-blue-600 outline-none disabled:opacity-50" />
            <button @click="pushComposer" :disabled="isLoading" 
              class="bg-blue-600 px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
              {{ isLoading ? 'Sending...' : 'Send' }}
            </button>
          </div>

          <div v-if="lastAttachment" class="mt-2">
            <span class="text-xs text-gray-300">Attached: {{ lastAttachment }}</span>
          </div>
        </div>
      </main>

      <!-- References -->
      <aside class="w-[25%] panel border-l border-panel p-5 overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-400 uppercase">References</div>
          <div v-if="allReferences.length > 0" class="text-xs text-gray-500">
            ({{ allReferences.length }})
          </div>
        </div>

        <ul class="space-y-4" v-if="allReferences.length > 0">
          <li v-for="(r, i) in allReferences" :key="i"
            class="bg-[#0c111a] p-4 rounded-xl border border-gray-800 shadow">
            <div class="flex justify-between mb-1">
              <div class="font-semibold text-sm">{{ r.title }}</div>
              <div class="text-xs text-gray-400">{{ r.year }}</div>
            </div>
            <div class="text-xs text-gray-400 mb-3">{{ r.snippet }}</div>
            <div class="flex gap-2">
              <button @click="openBubble('citation', r)" class="px-3 py-1 rounded bg-gray-800 text-xs">cite</button>
              <button @click="openPaper(r)" 
                class="px-3 py-1 rounded bg-gray-800 text-xs">
                open
              </button>
            </div>
          </li>
        </ul>

        <div v-else class="text-center py-8">
          <div class="text-gray-600 text-sm mb-2">No references yet</div>
          <div class="text-xs text-gray-700">
            Start a conversation to get relevant paper references
          </div>
        </div>
      </aside>
    </div>

    <!-- Modal -->
    <Transition name="fade">
      <div v-if="bubbleType" class="overlay" @click.self="closeBubble">
        <div class="bubble-card">
          <button class="bubble-close" @click="closeBubble">&times;</button>

          <div class="bubble-title">
            <span v-if="bubbleType === 'about'">About Us</span>
            <span v-else-if="bubbleType === 'help'">Help</span>
            <span v-else-if="bubbleType === 'citation'">Citation</span>
          </div>

          <div class="bubble-body">
            <div v-if="bubbleType === 'about'">
              <p class="mb-2 font-semibold">CompBuddy</p>
              <p>
                CompBuddy adalah chatbot yang membantu mahasiswa, peneliti, dan praktisi menemukan serta memahami paper di
                bidang Computer Science.
              </p>
            </div>

            <div v-else-if="bubbleType === 'help'">
              <p><em>Coming soon — help content placeholder.</em></p>
            </div>

            <div v-else-if="bubbleType === 'citation'">
              <div v-if="activeCitation">
                <p class="text-sm font-semibold mb-2 text-blue-900">{{ activeCitation.title }}</p>
                <p class="text-xs text-slate-600 mb-1">
                  <strong>Authors:</strong> {{ activeCitation.authors || 'N/A' }}
                </p>
                <p class="text-xs text-slate-600 mb-1">
                  <strong>Year:</strong> {{ activeCitation.year }}
                </p>
                <p class="text-xs text-slate-600 mb-1">
                  <strong>Venue:</strong> {{ activeCitation.venue || 'N/A' }}
                </p>
                <p class="text-xs text-slate-600 mb-3">
                  <strong>DOI:</strong> {{ activeCitation.doi || 'N/A' }}
                </p>
                
                <div class="bg-gray-50 p-3 rounded mb-3 border border-gray-200">
                  <p class="text-xs text-gray-500 mb-1">Summary:</p>
                  <p class="text-xs text-gray-700">{{ activeCitation.snippet }}</p>
                </div>

                <p class="text-xs text-gray-600 mb-2 font-semibold">Copy Citation Format:</p>
                <div class="flex flex-wrap gap-2 mb-2">
                  <button @click="copyCitation('apa')" 
                    class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs transition">
                    APA
                  </button>
                  <button @click="copyCitation('ieee')" 
                    class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs transition">
                    IEEE
                  </button>
                  <button @click="copyCitation('bibtex')" 
                    class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs transition">
                    BibTeX
                  </button>
                </div>
                <div v-if="copied" class="text-xs text-green-600 font-semibold mt-2">
                  ✓ Citation copied to clipboard!
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.cs-wallpaper {
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240"><defs><g id="icon" fill="none" stroke="%23394a5a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="32" height="24" rx="3" /><path d="M10 16h12" /></g></defs><rect width="100%" height="100%" fill="%2310191b"/><g opacity="0.08"><use href="%23icon" x="10" y="10" /><use href="%23icon" x="90" y="20" /><use href="%23icon" x="170" y="30" /><use href="%23icon" x="40" y="120" /><use href="%23icon" x="120" y="140" /><use href="%23icon" x="200" y="160" /></g></svg>');
  background-repeat: repeat;
  background-size: 240px;
}

.panel {
  background: rgba(15, 23, 36, 0.92);
  backdrop-filter: blur(4px);
}

.bubble-ai {
  background: #1c2535;
}

.bubble-user {
  background: #1854d4;
  color: white;
}

.border-panel {
  border-color: #1d2633;
}

.attach-btn {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.04);
  background: rgba(255, 255, 255, 0.02);
}

.attach-btn:active {
  transform: translateY(1px);
}

.overlay {
  position: fixed;
  inset: 0;
  background: rgba(7, 10, 14, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
}

.bubble-card {
  width: min(720px, 86%);
  background: white;
  color: #0b1220;
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 12px 40px rgba(2, 6, 23, 0.6);
  position: relative;
}

.bubble-title {
  color: #0b3b82;
  font-weight: 700;
  font-size: 20px;
  margin-bottom: 8px;
}

.bubble-close {
  position: absolute;
  right: 12px;
  top: 12px;
  background: transparent;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

.bubble-body {
  font-size: 14px;
  line-height: 1.45;
  color: #11202e;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Formatted AI Response Styles */
.formatted-content {
  line-height: 1.6;
}

.formatted-content strong {
  font-weight: 600;
  color: #e2e8f0;
}

.formatted-content .citation-link {
  color: #60a5fa;
  cursor: pointer;
  font-size: 0.75em;
  font-weight: 600;
  margin-left: 1px;
  transition: color 0.2s;
}

.formatted-content .citation-link:hover {
  color: #93c5fd;
  text-decoration: underline;
}

.formatted-content ul {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

.formatted-content li {
  margin: 0.25rem 0;
}
</style>