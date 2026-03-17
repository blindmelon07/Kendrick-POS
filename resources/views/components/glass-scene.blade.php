<div class="glass-scene-bg hidden lg:block" aria-hidden="true">
    {{-- SVG is blurred as a whole — this creates the soft, dreamy office backdrop --}}
    <svg style="width:100%;height:100%;filter:blur(10px);"
         viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice"
         xmlns="http://www.w3.org/2000/svg">
        <defs>
            <!-- Room ambient light -->
            <radialGradient id="gs-roomLight" cx="50%" cy="25%" r="60%">
                <stop offset="0%"   stop-color="#ddeeff" stop-opacity="0.9"/>
                <stop offset="100%" stop-color="#8aaec8" stop-opacity="1"/>
            </radialGradient>
            <!-- Bokeh soft white -->
            <radialGradient id="gs-bokeh1" cx="50%" cy="50%" r="50%">
                <stop offset="0%"   stop-color="#ffffff" stop-opacity="0.6"/>
                <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
            </radialGradient>
            <!-- Bokeh blue -->
            <radialGradient id="gs-bokeh2" cx="50%" cy="50%" r="50%">
                <stop offset="0%"   stop-color="#c8dff5" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="#c8dff5" stop-opacity="0"/>
            </radialGradient>
            <!-- Monitor screen glow -->
            <radialGradient id="gs-screenGlow" cx="50%" cy="50%" r="50%">
                <stop offset="0%"   stop-color="#a0c8f0" stop-opacity="0.7"/>
                <stop offset="100%" stop-color="#6090c0" stop-opacity="0.2"/>
            </radialGradient>
        </defs>

        <!-- ── Room background ── -->
        <rect width="1440" height="900" fill="url(#gs-roomLight)"/>
        <!-- Back wall subtle overlay -->
        <rect width="1440" height="560" fill="rgba(180,210,240,0.3)"/>
        <!-- Ceiling / top ambient light -->
        <ellipse cx="720" cy="0" rx="600" ry="200" fill="rgba(220,235,255,0.4)"/>

        <!-- ── Left monitor ── -->
        <!-- Stand neck -->
        <rect x="110" y="300" width="16" height="180" rx="4" fill="rgba(40,35,30,0.55)"/>
        <!-- Stand base -->
        <rect x="70"  y="475" width="96"  height="10"  rx="3" fill="rgba(40,35,30,0.55)"/>
        <!-- Outer bezel -->
        <rect x="30"  y="160" width="280" height="185" rx="10" fill="rgba(25,25,30,0.75)"/>
        <!-- Screen glass -->
        <rect x="38"  y="168" width="264" height="169" rx="6"  fill="url(#gs-screenGlow)"/>
        <rect x="38"  y="168" width="264" height="169" rx="6"  fill="rgba(100,160,220,0.25)"/>

        <!-- ── Right monitor ── -->
        <!-- Stand neck -->
        <rect x="1314" y="300" width="16"  height="180" rx="4" fill="rgba(40,35,30,0.55)"/>
        <!-- Stand base -->
        <rect x="1274" y="475" width="96"  height="10"  rx="3" fill="rgba(40,35,30,0.55)"/>
        <!-- Outer bezel -->
        <rect x="1120" y="150" width="310" height="200" rx="10" fill="rgba(25,25,30,0.75)"/>
        <!-- Screen glass -->
        <rect x="1129" y="158" width="292" height="184" rx="6"  fill="url(#gs-screenGlow)"/>
        <rect x="1129" y="158" width="292" height="184" rx="6"  fill="rgba(90,150,210,0.2)"/>

        <!-- ── Desk surface ── -->
        <rect x="0" y="480" width="1440" height="420" fill="rgba(85,65,48,0.60)"/>
        <!-- Desk edge highlight -->
        <rect x="0" y="480" width="1440" height="12"  fill="rgba(110,85,62,0.50)"/>

        <!-- ── Plant (right side) ── -->
        <!-- Pot -->
        <rect x="1330" y="390" width="60" height="90" rx="8" fill="rgba(80,55,40,0.70)"/>
        <!-- Leaves -->
        <ellipse cx="1340" cy="370" rx="22" ry="38" fill="rgba(55,100,55,0.75)"  transform="rotate(-15,1340,370)"/>
        <ellipse cx="1365" cy="350" rx="20" ry="42" fill="rgba(45, 90,45,0.80)"  transform="rotate(  5,1365,350)"/>
        <ellipse cx="1385" cy="365" rx="18" ry="35" fill="rgba(60,110,60,0.70)"  transform="rotate( 20,1385,365)"/>
        <ellipse cx="1355" cy="340" rx="15" ry="30" fill="rgba(70,120,70,0.65)"  transform="rotate( -5,1355,340)"/>

        <!-- ── Keyboard hint ── -->
        <rect x="540" y="496" width="360" height="24" rx="5" fill="rgba(50,40,35,0.45)"/>

        <!-- ── Bokeh circles ── -->
        <circle cx="200"  cy="120" r="80"  fill="url(#gs-bokeh1)" opacity="0.5"/>
        <circle cx="1250" cy="80"  r="60"  fill="url(#gs-bokeh2)" opacity="0.6"/>
        <circle cx="700"  cy="50"  r="100" fill="url(#gs-bokeh1)" opacity="0.3"/>
        <circle cx="950"  cy="200" r="50"  fill="url(#gs-bokeh2)" opacity="0.4"/>
        <circle cx="100"  cy="400" r="70"  fill="url(#gs-bokeh1)" opacity="0.25"/>
        <circle cx="1400" cy="300" r="90"  fill="url(#gs-bokeh2)" opacity="0.3"/>

        <!-- ── Window light (top centre) ── -->
        <ellipse cx="720" cy="10" rx="250" ry="120" fill="rgba(255,255,255,0.25)"/>
    </svg>
</div>
