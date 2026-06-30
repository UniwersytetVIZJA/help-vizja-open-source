import { Controller } from '@hotwired/stimulus'
import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

export default class extends Controller {
    static values = { eventsUrl: String, bookUrl: String }

    connect() {
        this.calendar = new Calendar(this.element, {
            locale: 'pl',
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            nowIndicator: true,
            selectable: true,
            events: this.eventsUrlValue, // GET /api/events (JSON)
            eventClick: async (info) => {
                const id = info.event.id
                const res = await fetch(this.bookUrlValue, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ eventId: id })
                })
                if (res.ok) {
                    this.calendar.refetchEvents()
                } else {
                    alert((await res.json()).message ?? 'Nie udało się zapisać')
                }
            }
        })
        this.calendar.render()
    }

    disconnect() {
        this.calendar?.destroy()
    }
}
