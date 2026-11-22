@if ($eventSlug)
    <input type="hidden" name="event" value="{{ $eventSlug }}">
@endif
@if ($raceSlug)
    <input type="hidden" name="race" value="{{ $raceSlug }}">
@endif
@if ($kit)
    <input type="hidden" name="kit" value="{{ $kit }}">
@endif
@if ($returnToRegistration)
    <input type="hidden" name="return" value="registration">
@endif
