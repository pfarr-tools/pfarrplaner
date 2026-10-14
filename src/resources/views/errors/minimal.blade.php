<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}code{font-family:monospace,monospace;font-size:1em}[hidden]{display:none}html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}code{font-family:Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace}svg,video{display:block;vertical-align:middle}video{max-width:100%;height:auto}.bg-white{--bg-opacity:1;background-color:#fff;background-color:rgba(255,255,255,var(--bg-opacity))}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-200{--border-opacity:1;border-color:#edf2f7;border-color:rgba(237,242,247,var(--border-opacity))}.border-gray-400{--border-opacity:1;border-color:#cbd5e0;border-color:rgba(203,213,224,var(--border-opacity))}.border-t{border-top-width:1px}.border-r{border-right-width:1px}.flex{display:flex}.grid{display:grid}.hidden{display:none}.items-center{align-items:center}.justify-center{justify-content:center}.font-semibold{font-weight:600}.h-5{height:1.25rem}.h-8{height:2rem}.h-16{height:4rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.leading-7{line-height:1.75rem}.mx-auto{margin-left:auto;margin-right:auto}.ml-1{margin-left:.25rem}.mt-2{margin-top:.5rem}.me-2{margin-right:.5rem}.ml-2{margin-left:.5rem}.mt-4{margin-top:1rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.ml-12{margin-left:3rem}.-mt-px{margin-top:-1px}.max-w-xl{max-width:36rem}.max-w-6xl{max-width:72rem}.min-h-screen{min-height:100vh}.overflow-hidden{overflow:hidden}.p-6{padding:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.pt-8{padding-top:2rem}.fixed{position:fixed}.relative{position:relative}.top-0{top:0}.right-0{right:0}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px 0 rgba(0,0,0,.06)}.text-center{text-align:center}.text-gray-200{--text-opacity:1;color:#edf2f7;color:rgba(237,242,247,var(--text-opacity))}.text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.text-gray-500{--text-opacity:1;color:#a0aec0;color:rgba(160,174,192,var(--text-opacity))}.text-gray-600{--text-opacity:1;color:#718096;color:rgba(113,128,150,var(--text-opacity))}.text-gray-700{--text-opacity:1;color:#4a5568;color:rgba(74,85,104,var(--text-opacity))}.text-gray-900{--text-opacity:1;color:#1a202c;color:rgba(26,32,44,var(--text-opacity))}.uppercase{text-transform:uppercase}.underline{text-decoration:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.tracking-wider{letter-spacing:.05em}.w-5{width:1.25rem}.w-8{width:2rem}.w-auto{width:auto}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}@-webkit-keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@-webkit-keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@-webkit-keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@-webkit-keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@media (min-width:640px){.sm\:rounded-lg{border-radius:.5rem}.sm\:block{display:block}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}.sm\:justify-between{justify-content:space-between}.sm\:h-20{height:5rem}.sm\:ml-0{margin-left:0}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:text-start{text-align:left}.sm\:text-end{text-align:right}}@media (min-width:768px){.md\:border-t-0{border-top-width:0}.md\:border-l{border-left-width:1px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-800{--bg-opacity:1;background-color:#2d3748;background-color:rgba(45,55,72,var(--bg-opacity))}.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}.dark\:text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}}
        </style>

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>

        <style>
            :root { --pf-blue: #1d4e89; --pf-ink: #203040; --pf-muted: #617386; --pf-line: #dce6ef; --pf-bg: #f4f8fb; }
            body { background: linear-gradient(145deg, #edf5fb 0%, #fbfcfd 58%, #f3efe6 100%); color: var(--pf-ink); }
            .error-card { width: min(100%, 720px); margin: 2rem auto; padding: 2.75rem; background: rgba(255,255,255,.96); border: 1px solid rgba(220,230,239,.9); border-radius: 1.25rem; box-shadow: 0 1rem 3rem rgba(32,48,64,.12); text-align: left; }
            .error-logo { display: block; width: 8rem; height: 8rem; margin: 0 auto 1.4rem; }
            .error-eyebrow { color: var(--pf-blue); font-size: .78rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; text-align: center; }
            .error-card h1 { margin: .4rem 0 .7rem; color: var(--pf-ink); font-size: clamp(1.8rem, 4vw, 2.5rem); line-height: 1.15; text-align: center; }
            .error-intro { max-width: 32rem; margin: 0 auto 1.7rem; color: var(--pf-muted); text-align: center; }
            .error-reference { display: flex; align-items: center; gap: .7rem; margin: 1rem 0 1.5rem; padding: .75rem 1rem; background: var(--pf-bg); border: 1px solid var(--pf-line); border-radius: .65rem; }
            .error-reference span { color: var(--pf-muted); font-size: .8rem; }
            .error-reference code { flex: 1; overflow: hidden; color: var(--pf-ink); font-size: .82rem; text-overflow: ellipsis; }
            .copy-button, .submit-button { border: 0; border-radius: .45rem; padding: .65rem 1rem; cursor: pointer; font-weight: 700; }
            .copy-button { background: #fff; border: 1px solid var(--pf-line); color: var(--pf-blue); font-size: .78rem; }
            .feedback-form { margin-top: 1.2rem; padding-top: 1.4rem; border-top: 1px solid var(--pf-line); }
            .feedback-form label { display: block; margin-bottom: .35rem; color: var(--pf-ink); font-size: .88rem; font-weight: 700; }
            .feedback-form label span { color: var(--pf-muted); font-weight: 400; }
            .feedback-form textarea, .feedback-form input { display: block; width: 100%; margin-bottom: 1rem; padding: .7rem .8rem; border: 1px solid #cbd8e3; border-radius: .45rem; color: var(--pf-ink); font: inherit; }
            .feedback-form textarea:focus, .feedback-form input:focus { border-color: var(--pf-blue); outline: 3px solid rgba(29,78,137,.14); }
            .feedback-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
            .submit-button { background: var(--pf-blue); color: #fff; }
            .feedback-message { margin: 1rem 0; padding: .9rem 1rem; border-radius: .5rem; font-size: .9rem; }
            .feedback-message.success { background: #e8f6ef; color: #1a6944; }
            .feedback-message.warning { background: #fff5dd; color: #805a12; }
            .field-error { margin: -.4rem 0 1rem; color: #a52f3b; font-size: .82rem; }
            .back-link { display: block; margin-top: 1.6rem; color: var(--pf-blue); font-size: .9rem; font-weight: 700; text-align: center; }
            .error-code-label { color: var(--pf-muted); font-size: .8rem; }
            @media (max-width: 640px) { .error-card { margin: 1rem; padding: 1.6rem; } .feedback-row { grid-template-columns: 1fr; gap: 0; } .error-reference { align-items: flex-start; flex-wrap: wrap; } .error-reference code { flex-basis: 100%; order: 3; } }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                        @yield('code')
                    </div>

                    <div class="ms-4 text-lg text-gray-500 uppercase tracking-wider">
                        @yield('message')
                    </div>
                </div>
                <div class="py-4 text-center">
                    @yield('button')
                </div>
            </div>
        </div>
    </body>
</html>
