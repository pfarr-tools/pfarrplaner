Option Explicit

Global g_bStarted       As Boolean
Global g_oGEB           As Object
Global g_oGEBListener   As Object
Global g_oController    As Object
Global g_oSSListener    As Object
Global g_dLoopStartTime As Date
Global g_nPendingJump   As Long

' ============================================================
' An "Dokument öffnen" hängen
' ============================================================
Sub StartWatchdog()
    If g_bStarted Then Exit Sub
    g_bStarted     = True
    g_nPendingJump = -1

    Dim ctx As Object
    ctx    = GetDefaultContext()
    g_oGEB = ctx.getByName("/singletons/com.sun.star.frame.theGlobalEventBroadcaster")
    If IsNull(g_oGEB) Or IsEmpty(g_oGEB) Then Exit Sub

    g_oGEBListener = CreateUnoListener("GEB_", "com.sun.star.document.XEventListener")
    g_oGEB.addEventListener(g_oGEBListener)
End Sub

' ============================================================
' GlobalEventBroadcaster callbacks
' ============================================================
Sub GEB_notifyEvent(oEv As Object)
    Dim sName As String
    sName = CStr(oEv.EventName)

    If sName = "OnViewCreated" Or sName = "OnFocus" Then
        If Not IsNull(g_oController) Then Exit Sub
        Dim oPres As Object
        oPres = ThisComponent.Presentation
        If Not oPres.isRunning() Then Exit Sub

        Wait 300
        g_oController = oPres.getController()
        If IsNull(g_oController) Or IsEmpty(g_oController) Then
            Wait 500
            g_oController = oPres.getController()
        End If
        If IsNull(g_oController) Or IsEmpty(g_oController) Then Exit Sub

        g_oSSListener = CreateUnoListener("EV_", _
            "com.sun.star.presentation.XSlideShowListener")
        g_oController.addSlideShowListener(g_oSSListener)
    End If

    If sName = "OnViewClosed" Then
        If Not IsNull(g_oController) And Not IsNull(g_oSSListener) Then
            On Error Resume Next
            g_oController.removeSlideShowListener(g_oSSListener)
        End If
        g_oSSListener = Nothing
        g_oController = Nothing
    End If
End Sub

Sub GEB_disposing(oEv As Object)
    StopWatchdog()
End Sub

Sub StopWatchdog()
    If Not IsNull(g_oGEB) And Not IsNull(g_oGEBListener) Then
        On Error Resume Next
        g_oGEB.removeEventListener(g_oGEBListener)
    End If
    g_oGEBListener = Nothing
    g_oGEB         = Nothing
    g_bStarted     = False
End Sub

' ============================================================
' SlideShow Listener callbacks
' ============================================================
Sub EV_slideTransitionStarted(oEv)
    ' Pendenden Sprung ausführen falls gesetzt
    If g_nPendingJump >= 0 Then
        Dim nTarget As Long
        nTarget        = g_nPendingJump
        g_nPendingJump = -1
        g_oController.gotoSlideIndex(nTarget)
        Exit Sub
    End If

    ' Zeitpunkt merken wenn LOOP_END betreten wird
    Dim nCurIdx As Integer
    nCurIdx = g_oController.getCurrentSlideIndex()
    If Left(ThisComponent.DrawPages.getByIndex(nCurIdx).Name, 9) = "LOOP_END_" Then
        g_dLoopStartTime = Now()
    End If
End Sub

Sub EV_slideEnded(bReverse As Boolean)
    If bReverse Then Exit Sub

    Dim nCurIdx   As Integer
    Dim oCurSlide As Object
    Dim nSeconds  As Double
    Dim nTargetIdx As Long
    Dim i         As Integer

    nCurIdx   = g_oController.getCurrentSlideIndex()
    oCurSlide = ThisComponent.DrawPages.getByIndex(nCurIdx)

    If Left(oCurSlide.Name, 9) <> "LOOP_END_" Then Exit Sub

    nSeconds = oCurSlide.TransitionDuration

    ' Manueller Klick?
    If (Now() - g_dLoopStartTime) * 86400 < nSeconds - 0.5 Then Exit Sub

    ' Timer abgelaufen → rückwärts LOOP_START_ suchen
    nTargetIdx = -1
    For i = nCurIdx - 1 To 0 Step -1
        If Left(ThisComponent.DrawPages.getByIndex(i).Name, 11) = "LOOP_START_" Then
            nTargetIdx = i
            Exit For
        End If
    Next i
    If nTargetIdx = -1 Then Exit Sub

    g_nPendingJump = nTargetIdx
End Sub

' Stubs
Sub EV_paused(oEv)               : End Sub
Sub EV_resumed(oEv)              : End Sub
Sub EV_slideTransitionEnded(oEv) : End Sub
Sub EV_slideAnimationEnded(oEv)  : End Sub
Sub EV_hyperLinkClicked(oEv)     : End Sub
Sub EV_disposing(oEv)            : End Sub
