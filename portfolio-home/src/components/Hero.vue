<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { animate } from 'motion'
import { Cpu, Leaf, ScanLine, Sparkles } from 'lucide-vue-next'

const heroRef = ref(null)
const rainCanvasRef = ref(null)

const pointer = ref({ x: 0, y: 0 })
const easedPointer = ref({ x: 0, y: 0 })

const backFoliage = [
  { id: 'bg-1', src: '/leaves/fern.svg', left: 7, top: 14, size: 190, rotate: -12 },
  { id: 'bg-2', src: '/leaves/fern.svg', left: 72, top: 10, size: 210, rotate: 15 },
  { id: 'bg-3', src: '/leaves/monstera.svg', left: 20, top: 55, size: 165, rotate: 8 },
  { id: 'bg-4', src: '/leaves/fern.svg', left: 82, top: 54, size: 150, rotate: -17 },
]

const middleLeaves = [
  { id: 'mid-1', src: '/leaves/monstera.svg', left: 11, top: 28, size: 220, depth: 35, rotate: -10, bob: 9, duration: 10.2 },
  { id: 'mid-2', src: '/leaves/fern.svg', left: 75, top: 24, size: 210, depth: 31, rotate: 16, bob: 10, duration: 9.6 },
  { id: 'mid-3', src: '/leaves/fern.svg', left: 18, top: 69, size: 190, depth: 26, rotate: 12, bob: 8, duration: 11.3 },
  { id: 'mid-4', src: '/leaves/monstera.svg', left: 70, top: 66, size: 200, depth: 28, rotate: -8, bob: 8, duration: 10.1 },
]

const frontLeaves = [
  { id: 'fg-1', src: '/leaves/monstera.svg', side: 'left', size: 410, rotate: 18, bob: 6, duration: 11.2 },
  { id: 'fg-2', src: '/leaves/monstera.svg', side: 'right', size: 430, rotate: -18, bob: 7, duration: 10.5 },
]

const middleLeafRefs = new Map()
const frontLeafRefs = new Map()

let rainFrame = 0
let pointerFrame = 0
let resizeHandler = null
let reducedMotion = false

const heroVars = computed(() => ({
  '--pointer-x': `${easedPointer.value.x}px`,
  '--pointer-y': `${easedPointer.value.y}px`,
}))

const vMotion = {
  mounted(el, binding) {
    if (reducedMotion) return

    const delay = binding?.value?.delay ?? 0
    animate(
      el,
      {
        opacity: [0, 1],
        y: [16, 0],
        filter: ['blur(5px)', 'blur(0px)'],
      },
      {
        duration: 0.75,
        delay,
        ease: [0.2, 0.7, 0.2, 1],
      },
    )
  },
}

const setMiddleLeaf = (id, el) => {
  if (el) {
    middleLeafRefs.set(id, el)
    return
  }

  middleLeafRefs.delete(id)
}

const setFrontLeaf = (id, el) => {
  if (el) {
    frontLeafRefs.set(id, el)
    return
  }

  frontLeafRefs.delete(id)
}

const onMouseMove = (event) => {
  const rect = heroRef.value?.getBoundingClientRect()
  if (!rect) return

  const x = (event.clientX - rect.left) / rect.width - 0.5
  const y = (event.clientY - rect.top) / rect.height - 0.5

  pointer.value = {
    x: x * 28,
    y: y * 20,
  }
}

const onMouseLeave = () => {
  pointer.value = { x: 0, y: 0 }
}

const smoothPointer = () => {
  if (reducedMotion) {
    easedPointer.value = { x: 0, y: 0 }
    return
  }

  easedPointer.value.x += (pointer.value.x - easedPointer.value.x) * 0.08
  easedPointer.value.y += (pointer.value.y - easedPointer.value.y) * 0.08

  pointerFrame = requestAnimationFrame(smoothPointer)
}

const startDigitalRain = () => {
  if (reducedMotion) return

  const canvas = rainCanvasRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const glyphs = '01<>[]{}:/|+*ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  const fontSize = 13
  let columns = 0
  let drops = []

  const resizeCanvas = () => {
    const rect = canvas.getBoundingClientRect()
    const ratio = window.devicePixelRatio || 1

    canvas.width = Math.floor(rect.width * ratio)
    canvas.height = Math.floor(rect.height * ratio)

    ctx.setTransform(1, 0, 0, 1, 0, 0)
    ctx.scale(ratio, ratio)

    columns = Math.max(1, Math.floor(rect.width / 24))
    drops = new Array(columns).fill(0).map(() => -Math.random() * 70)
  }

  resizeHandler = resizeCanvas
  resizeCanvas()
  window.addEventListener('resize', resizeHandler)

  const draw = () => {
    const width = canvas.clientWidth
    const height = canvas.clientHeight

    ctx.fillStyle = 'rgba(236, 253, 245, 0.06)'
    ctx.fillRect(0, 0, width, height)

    ctx.font = `500 ${fontSize}px "JetBrains Mono", "Fira Code", monospace`
    ctx.shadowColor = 'rgba(16, 185, 129, 0.35)'
    ctx.shadowBlur = 8

    for (let i = 0; i < columns; i += 1) {
      const text = glyphs[Math.floor(Math.random() * glyphs.length)]
      const x = i * 24 + 9
      const y = drops[i] * fontSize

      ctx.fillStyle = i % 5 === 0 ? 'rgba(16, 185, 129, 0.34)' : 'rgba(52, 211, 153, 0.22)'
      ctx.fillText(text, x, y)

      if (y > height + 30 && Math.random() > 0.985) {
        drops[i] = 0
      }

      drops[i] += 0.3 + Math.random() * 0.3
    }

    rainFrame = requestAnimationFrame(draw)
  }

  draw()
}

const startLeafMotion = () => {
  if (reducedMotion) return

  middleLeaves.forEach((leaf, index) => {
    const target = middleLeafRefs.get(leaf.id)
    if (!target) return

    animate(
      target,
      {
        y: [-leaf.bob, leaf.bob, -leaf.bob],
        rotate: [leaf.rotate - 1.2, leaf.rotate + 1.2, leaf.rotate - 1.2],
      },
      {
        duration: leaf.duration,
        delay: index * 0.16,
        repeat: Infinity,
        ease: 'ease-in-out',
      },
    )
  })

  frontLeaves.forEach((leaf, index) => {
    const target = frontLeafRefs.get(leaf.id)
    if (!target) return

    animate(
      target,
      {
        y: [-leaf.bob, leaf.bob, -leaf.bob],
      },
      {
        duration: leaf.duration,
        delay: index * 0.2,
        repeat: Infinity,
        ease: 'ease-in-out',
      },
    )
  })
}

const frontLeafClass = (side) => {
  if (side === 'left') return 'left-[-150px] bottom-[-170px]'

  return 'right-[-160px] bottom-[-170px]'
}

onMounted(() => {
  reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  smoothPointer()
  startDigitalRain()
  startLeafMotion()
})

onBeforeUnmount(() => {
  cancelAnimationFrame(rainFrame)
  cancelAnimationFrame(pointerFrame)

  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler)
  }
})
</script>

<template>
  <section
    ref="heroRef"
    class="hero relative isolate min-h-screen overflow-hidden"
    :style="heroVars"
    @mousemove="onMouseMove"
    @mouseleave="onMouseLeave"
  >
    <div class="skyline-base" />
    <div class="tower-silhouettes" />

    <div class="rain-clip">
      <canvas ref="rainCanvasRef" class="digital-rain" aria-hidden="true" />
    </div>

    <div class="absolute inset-0 z-10 pointer-events-none">
      <img
        v-for="leaf in backFoliage"
        :key="leaf.id"
        :src="leaf.src"
        alt=""
        class="absolute select-none opacity-35 blur-[8px]"
        :style="{
          left: `${leaf.left}%`,
          top: `${leaf.top}%`,
          width: `${leaf.size}px`,
          transform: `rotate(${leaf.rotate}deg)`,
        }"
      />
    </div>

    <div class="absolute inset-0 z-20 pointer-events-none">
      <div
        v-for="leaf in middleLeaves"
        :key="leaf.id"
        class="absolute"
        :style="{
          left: `${leaf.left}%`,
          top: `${leaf.top}%`,
          width: `${leaf.size}px`,
          transform: `translate3d(calc(var(--pointer-x) * ${-leaf.depth / 28}), calc(var(--pointer-y) * ${-leaf.depth / 28}), 0)`,
        }"
      >
        <img
          :ref="(el) => setMiddleLeaf(leaf.id, el)"
          :src="leaf.src"
          alt=""
          class="block w-full select-none opacity-80 drop-shadow-[0_20px_40px_rgba(16,185,129,0.25)]"
        />
      </div>
    </div>

    <div class="relative z-30 grid min-h-screen place-items-center px-6 py-16">
      <article class="city-card relative w-[min(780px,calc(100vw-36px))] rounded-[26px] border border-emerald-900/18 bg-white/44 p-6 shadow-[0_25px_80px_rgba(15,23,42,0.15)] backdrop-blur-lg sm:p-8">
        <div class="pointer-events-none absolute left-5 right-5 top-5 h-px bg-gradient-to-r from-transparent via-emerald-400/70 to-transparent" />
        <div class="pointer-events-none absolute bottom-5 left-5 right-5 h-px bg-gradient-to-r from-transparent via-emerald-300/45 to-transparent" />

        <div class="mb-5 flex flex-wrap items-center gap-3 text-[11px] uppercase tracking-[0.2em] text-emerald-900/65">
          <span class="inline-flex items-center gap-2 rounded-full border border-emerald-900/15 bg-white/50 px-3 py-1.5"><Leaf :size="13" /> Bio-Urban Grid</span>
          <span class="inline-flex items-center gap-2 rounded-full border border-emerald-900/15 bg-white/45 px-3 py-1.5"><Cpu :size="13" /> Forest City / Johor</span>
          <span class="inline-flex items-center gap-2 rounded-full border border-emerald-900/15 bg-white/45 px-3 py-1.5"><ScanLine :size="13" /> Smart Layer Active</span>
        </div>

        <p class="absolute right-6 top-4 font-mono text-[11px] text-emerald-900/55">1.4636° N, 103.5239° E</p>

        <h1
          v-motion="{ delay: 0.05 }"
          class="font-[Montserrat] text-[clamp(2.1rem,6vw,4.7rem)] font-extrabold leading-[0.95] tracking-[-0.03em] text-transparent bg-gradient-to-r from-slate-700 via-slate-500 to-emerald-700 bg-clip-text"
        >
          Sergio Quinteros
        </h1>

        <p v-motion="{ delay: 0.16 }" class="mt-4 max-w-[56ch] font-[Inter] text-[clamp(1rem,2.2vw,1.3rem)] font-medium text-emerald-950/88">
          Full-Stack Developer | Bio-Futurism Engineering
        </p>

        <div v-motion="{ delay: 0.27 }" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-emerald-900/15 bg-white/45 px-4 py-2.5 text-[0.94rem] text-emerald-950/78">
          <Sparkles :size="15" />
          <span>Activating urban-scale software for nature-integrated smart systems.</span>
        </div>
      </article>
    </div>

    <div class="absolute inset-0 z-40 pointer-events-none">
      <img
        v-for="leaf in frontLeaves"
        :key="leaf.id"
        :ref="(el) => setFrontLeaf(leaf.id, el)"
        :src="leaf.src"
        alt=""
        class="front-leaf absolute select-none opacity-[0.8] blur-[2px]"
        :class="frontLeafClass(leaf.side)"
        :style="{ width: `${leaf.size}px`, transform: `rotate(${leaf.rotate}deg)` }"
      />
    </div>
  </section>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&family=Montserrat:wght@600;700;800&display=swap');

.hero {
  font-family: Inter, sans-serif;
}

.skyline-base {
  position: absolute;
  inset: 0;
  z-index: 0;
  background: linear-gradient(to bottom right, #f8fafc 0%, #ecfdf5 100%);
}

.tower-silhouettes {
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(90deg, rgba(255, 255, 255, 0.78) 0 9%, transparent 9% 14%, rgba(255, 255, 255, 0.7) 14% 25%, transparent 25% 29%, rgba(255, 255, 255, 0.74) 29% 40%, transparent 40% 44%, rgba(255, 255, 255, 0.76) 44% 57%, transparent 57% 62%, rgba(255, 255, 255, 0.73) 62% 72%, transparent 72% 76%, rgba(255, 255, 255, 0.79) 76% 88%, transparent 88% 92%, rgba(255, 255, 255, 0.75) 92% 100%),
    linear-gradient(0deg, rgba(255, 255, 255, 0.72) 0 30%, transparent 30% 100%);
  mask-image: linear-gradient(to top, black 0%, black 78%, transparent 100%);
  -webkit-mask-image: linear-gradient(to top, black 0%, black 78%, transparent 100%);
}

.rain-clip {
  position: absolute;
  inset: 0;
  z-index: 5;
  mask-image:
    linear-gradient(90deg, black 0 9%, transparent 9% 14%, black 14% 25%, transparent 25% 29%, black 29% 40%, transparent 40% 44%, black 44% 57%, transparent 57% 62%, black 62% 72%, transparent 72% 76%, black 76% 88%, transparent 88% 92%, black 92% 100%),
    linear-gradient(to top, black 0%, black 72%, transparent 100%);
  mask-composite: intersect;
  -webkit-mask-image:
    linear-gradient(90deg, black 0 9%, transparent 9% 14%, black 14% 25%, transparent 25% 29%, black 29% 40%, transparent 40% 44%, black 44% 57%, transparent 57% 62%, black 62% 72%, transparent 72% 76%, black 76% 88%, transparent 88% 92%, black 92% 100%),
    linear-gradient(to top, black 0%, black 72%, transparent 100%);
  -webkit-mask-composite: source-in;
  opacity: 0.9;
}

.digital-rain {
  width: 100%;
  height: 100%;
  display: block;
  filter: blur(0.2px);
}

.city-card {
  box-shadow:
    0 0 0 1px rgba(255, 255, 255, 0.36),
    0 25px 80px rgba(15, 23, 42, 0.15);
}

@media (max-width: 780px) {
  .city-card {
    border-radius: 20px;
  }

  .front-leaf {
    width: min(70vw, 300px) !important;
  }
}
</style>
